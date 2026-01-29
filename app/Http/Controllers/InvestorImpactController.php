<?php

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InvestorImpactController extends Controller
{
    /**
     * Get investor impact metrics (Admin/Investor only).
     *
     * GET /api/investor-impact/metrics
     *
     * Headers: Authorization: Bearer {token}
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "roi": 15.5,
     *     "total_impact": 125000,
     *     "mosques_served": 127,
     *     "total_donations": 45230,
     *     "water_delivered": 12450
     *   }
     * }
     */
    public function metrics(Request $request): JsonResponse
    {
        // Check if user is admin or investor
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'investor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or investor can access investor impact metrics.',
            ], 403);
        }

        // Calculate metrics with caching
        $cacheKey = 'investor_impact_metrics';

        $data = Cache::remember($cacheKey, 300, function () {
            $totalDonations = Donation::where('status', 'completed')->count();
            $totalImpact = Donation::where('status', 'completed')->sum('amount');
            $mosquesServed = Delivery::where('status', 'delivered')
                ->distinct('mosque_id')
                ->count('mosque_id');
            $waterDelivered = Delivery::where('status', 'delivered')->sum('liters_delivered');

            // ROI calculation: percentage based on water delivered vs total impact
            // Using the same calculation pattern as DashboardController for consistency
            $roi = $totalImpact > 0
                ? round(($waterDelivered / $totalImpact) * 100, 1)
                : 0;

            return [
                'roi' => (float) $roi,
                'total_impact' => (float) $totalImpact,
                'mosques_served' => $mosquesServed,
                'total_donations' => $totalDonations,
                'water_delivered' => (int) $waterDelivered,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get funnel flow data (Admin/Investor only).
     *
     * GET /api/investor-impact/funnel
     *
     * Headers: Authorization: Bearer {token}
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "stage": {
     *         "en": "Donations",
     *         "ar": "التبرعات"
     *       },
     *       "value": 100
     *     },
     *     {
     *       "stage": {
     *         "en": "Delivery",
     *         "ar": "التسليم"
     *       },
     *       "value": 85
     *     },
     *     {
     *       "stage": {
     *         "en": "Impact",
     *         "ar": "التأثير"
     *       },
     *       "value": 75
     *     }
     *   ]
     * }
     */
    public function funnel(Request $request): JsonResponse
    {
        // Check if user is admin or investor
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'investor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or investor can access investor impact funnel.',
            ], 403);
        }

        // Calculate funnel values with caching
        $cacheKey = 'investor_impact_funnel';

        $data = Cache::remember($cacheKey, 300, function () {
            // Calculate funnel values based on actual data
            // Funnel represents conversion flow: Donations -> Delivery -> Impact
            $totalDonations = Donation::where('status', 'completed')->count();
            $totalDeliveries = Delivery::where('status', 'delivered')->count();
            $totalWaterDelivered = Delivery::where('status', 'delivered')->sum('liters_delivered');

            // Stage 1: Donations (always 100% as base)
            $donationsValue = 100;

            // Stage 2: Delivery - percentage of donations that resulted in deliveries
            $deliveryValue = $totalDonations > 0
                ? round(($totalDeliveries / $totalDonations) * 100, 0)
                : 0;

            // Stage 3: Impact - percentage based on delivery efficiency
            // Calculate as percentage of deliveries that achieved meaningful impact
            // Using average water per delivery relative to a baseline
            $avgWaterPerDelivery = $totalDeliveries > 0
                ? ($totalWaterDelivered / $totalDeliveries)
                : 0;
            // Baseline: 1000 liters per delivery = 100% impact
            $baselineLitersPerDelivery = 1000;
            $impactValue = $baselineLitersPerDelivery > 0
                ? round(($avgWaterPerDelivery / $baselineLitersPerDelivery) * 100, 0)
                : 0;

            // Ensure values are within reasonable bounds (0-100)
            $deliveryValue = min(100, max(0, $deliveryValue));
            $impactValue = min(100, max(0, $impactValue));

            return [
                [
                    'stage' => [
                        'en' => 'Donations',
                        'ar' => 'التبرعات',
                    ],
                    'value' => $donationsValue,
                ],
                [
                    'stage' => [
                        'en' => 'Delivery',
                        'ar' => 'التسليم',
                    ],
                    'value' => $deliveryValue,
                ],
                [
                    'stage' => [
                        'en' => 'Impact',
                        'ar' => 'التأثير',
                    ],
                    'value' => $impactValue,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }
}

