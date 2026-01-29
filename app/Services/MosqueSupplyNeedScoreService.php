<?php namespace App\Services;

use App\Models\Mosque;
use Carbon\Carbon;

class MosqueSupplyNeedScoreService
{
    public function updateSuppliesNeedScores(Mosque $mosque): void
    {
        foreach ($mosque->supplies as $supply) {
            $score = $this->calculateNeedScore($supply);

            if ($score >= 70) {
                $needLevel = 'High';
            } elseif ($score >= 40) {
                $needLevel = 'Medium';
            } else {
                $needLevel = 'Low';
            }

            $supply->update([
                'need_score' => $score,
                'need_level' => $needLevel,
            ]);
        }
    }

    public function calculateNeedScore($supply): int
    {
        $score = 0;

        // 1. الكمية الحالية مقابل المطلوبة (0-60)
        if ($supply->required_quantity > 0) {
            $percentage = ($supply->current_quantity / $supply->required_quantity) * 100;

            if ($percentage < 20) {
                $score += 60;
            } elseif ($percentage < 50) {
                $score += 40;
            } elseif ($percentage < 80) {
                $score += 20;
            }
        }

        // 2. الزمن منذ آخر تبرع (0-25)
        $lastDonation = $supply->updated_at;
        $days = Carbon::now()->diffInDays($lastDonation);

        if ($days >= 30) {
            $score += 25;
        } elseif ($days >= 14) {
            $score += 15;
        } elseif ($days >= 7) {
            $score += 5;
        }

        // 3. عامل النشاط (0-15)
        if ($supply->is_active) {
            $score += 15;
        }

        return min(100, $score);
    }
}
