<?php

namespace App\Services;

use App\Models\Mosque;
use Carbon\Carbon;

/**
 * Service to calculate AI need score for mosques.
 * 
 * According to specification:
 * The need_score for mosques should be calculated based on:
 * - Current water level vs required water level
 * - Time since last delivery
 * - Historical usage patterns
 * - Geographic factors
 */
class MosqueNeedScoreService
{
    /**
     * Calculate need score for a mosque (0-100).
     * 
     * @param Mosque $mosque
     * @return int Score from 0 to 100
     */
    public function calculateNeedScore(Mosque $mosque): int
    {
        $score = 0;

        // 1. Current water level vs required water level (0-50 points)
        $waterLevelScore = $this->calculateWaterLevelScore($mosque);
        $score += $waterLevelScore;

        // 2. Time since last delivery (0-30 points)
        $timeSinceDeliveryScore = $this->calculateTimeSinceDeliveryScore($mosque);
        $score += $timeSinceDeliveryScore;

        // 3. Historical usage patterns (0-15 points)
        $historicalUsageScore = $this->calculateHistoricalUsageScore($mosque);
        $score += $historicalUsageScore;

        // 4. Geographic factors (0-5 points)
        $geographicScore = $this->calculateGeographicScore($mosque);
        $score += $geographicScore;

        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }

    /**
     * Calculate score based on water level (0-50 points).
     * Higher score if water level is lower.
     */
    private function calculateWaterLevelScore(Mosque $mosque): int
    {
        if ($mosque->required_water_level == 0) {
            return 0;
        }

        $percentage = ($mosque->current_water_level / $mosque->required_water_level) * 100;
        
        // If water level is below 20% of required, give maximum score
        if ($percentage < 20) {
            return 50;
        }
        
        // If water level is above 100% of required, give minimum score
        if ($percentage >= 100) {
            return 0;
        }

        // Linear scale: 0% = 50 points, 100% = 0 points
        return (int) (50 * (1 - ($percentage / 100)));
    }

    /**
     * Calculate score based on time since last delivery (0-30 points).
     * Higher score if more time has passed since last delivery.
     */
    private function calculateTimeSinceDeliveryScore(Mosque $mosque): int
    {
        $lastDelivery = $mosque->deliveries()
            ->where('status', 'delivered')
            ->orderBy('actual_delivery_date', 'desc')
            ->first();

        if (!$lastDelivery || !$lastDelivery->actual_delivery_date) {
            // No delivery history, give high score
            return 30;
        }

        $daysSinceDelivery = Carbon::now()->diffInDays($lastDelivery->actual_delivery_date);

        // More than 30 days = maximum score (30 points)
        if ($daysSinceDelivery >= 30) {
            return 30;
        }

        // Less than 7 days = minimum score (0 points)
        if ($daysSinceDelivery < 7) {
            return 0;
        }

        // Linear scale: 7 days = 0 points, 30 days = 30 points
        return (int) (30 * (($daysSinceDelivery - 7) / 23));
    }

    /**
     * Calculate score based on historical usage patterns (0-15 points).
     * Higher score if mosque has high historical usage.
     */
    private function calculateHistoricalUsageScore(Mosque $mosque): int
    {
        // Get average water delivered per month over last 3 months
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        $totalDelivered = $mosque->deliveries()
            ->where('status', 'delivered')
            ->where('actual_delivery_date', '>=', $threeMonthsAgo)
            ->sum('liters_delivered');

        $averagePerMonth = $totalDelivered / 3;

        // If average is high (more than 50% of required), give higher score
        if ($mosque->required_water_level > 0) {
            $usagePercentage = ($averagePerMonth / $mosque->required_water_level) * 100;
            
            if ($usagePercentage > 80) {
                return 15;
            } elseif ($usagePercentage > 50) {
                return 10;
            } elseif ($usagePercentage > 20) {
                return 5;
            }
        }

        return 0;
    }

    /**
     * Calculate score based on geographic factors (0-5 points).
     * Higher score for mosques in high-demand areas.
     */
    private function calculateGeographicScore(Mosque $mosque): int
    {
        // Simple geographic scoring based on location
        // In a real implementation, this could consider:
        // - Distance from water sources
        // - Population density
        // - Climate factors
        // - Accessibility
        
        // For now, give a small base score if mosque is active
        return $mosque->is_active ? 2 : 0;
    }

    /**
     * Update need level based on need score.
     * 
     * @param Mosque $mosque
     * @return void
     */
    public function updateNeedLevel(Mosque $mosque): void
    {
        $score = $this->calculateNeedScore($mosque);
        
        if ($score >= 70) {
            $needLevel = 'High';
        } elseif ($score >= 40) {
            $needLevel = 'Medium';
        } else {
            $needLevel = 'Low';
        }

        $mosque->update([
            'need_score' => $score,
            'need_level' => $needLevel,
        ]);
    }
}

