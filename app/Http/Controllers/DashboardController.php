<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\Donation;
use App\Models\Delivery;
use App\Models\Mosque;
use App\Models\Truck;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics based on user role.
     *
     * GET /api/dashboard/stats
     *
     * Headers: Authorization: Bearer {token}
     *
     * Response (200) - Admin:
     * {
     *   "success": true,
     *   "data": {
     *     "total_donations": 45230,
     *     "water_delivered": 12450,
     *     "mosques_needing_supply": 23,
     *     "active_fleet": 15
     *   }
     * }
     *
     * Response (200) - Auditor:
     * {
     *   "success": true,
     *   "data": {
     *     "total_revenue": 125000,
     *     "verified_donations": 1245,
     *     "pending_verification": 12,
     *     "compliance_score": 98
     *   }
     * }
     *
     * Response (200) - Investor:
     * {
     *   "success": true,
     *   "data": {
     *     "total_impact": 125000,
     *     "mosques_served": 127,
     *     "water_delivered": 12450,
     *     "roi": 15.5
     *   }
     * }
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $role = $user->role;
        $cacheKey = 'dashboard_stats_' . $role . '_' . $user->id;

        $stats = Cache::remember($cacheKey, 300, function () use ($role) {
            if ($role === 'admin') {
                // Admin stats
                $totalCompletedDonations = Donation::where('status', 'completed')->count();
                $totalDonations=Donation::count();
                $waterDelivered = Delivery::where('status', 'delivered')->sum('liters_delivered');
                $mosquesNeedingSupply = Mosque::whereColumn('current_water_level', '<', 'required_water_level')
                    ->where('is_active', true)
                    ->count();
                $activeFleet = Truck::where('status', 'active')->count();
                $activeMosquesCount = Mosque::where('is_active', true)->count();
                $allMosquesCount = Mosque::count();

                return [
                    'total_donations' => $totalDonations,
                    'total_completed_donations' => $totalCompletedDonations,
                    'water_delivered' => (int) $waterDelivered,
                    'mosques_needing_supply' => $mosquesNeedingSupply,
                    'active_fleet' => $activeFleet,
                    'active_mosques_count' => $activeMosquesCount,
                    'all_mosques_count' => $allMosquesCount,
                ];
            } elseif ($role === 'auditor') {
                // Auditor stats
                $totalRevenue = Donation::where('status', 'completed')->sum('amount');
                $verifiedDonations = Donation::where('verified', true)->count();
                $pendingVerification = Donation::where('verified', false)
                    ->where('status', 'completed')
                    ->count();

                // Compliance score calculation: percentage of completed donations that are verified
                $completedDonations = Donation::where('status', 'completed')->count();
                $complianceScore = $completedDonations > 0
                    ? round(($verifiedDonations / $completedDonations) * 100, 0)
                    : 100;

                return [
                    'total_revenue' => (float) $totalRevenue,
                    'verified_donations' => $verifiedDonations,
                    'pending_verification' => $pendingVerification,
                    'compliance_score' => (int) $complianceScore,
                ];
            } elseif ($role === 'investor') {
                // Investor stats
                $totalImpact = Donation::where('status', 'completed')->sum('amount');
                $mosquesServed = Delivery::where('status', 'delivered')
                    ->distinct('mosque_id')
                    ->count('mosque_id');
                $waterDelivered = Delivery::where('status', 'delivered')->sum('liters_delivered');

                // ROI calculation: simplified as percentage (this would need actual investment data)
                // For now, using a mock calculation based on water delivered vs donations
                $totalDonationsAmount = Donation::where('status', 'completed')->sum('amount');
                $roi = $totalDonationsAmount > 0
                    ? round(($waterDelivered / $totalDonationsAmount) * 100, 1)
                    : 0;

                return [
                    'total_impact' => (float) $totalImpact,
                    'mosques_served' => $mosquesServed,
                    'water_delivered' => (int) $waterDelivered,
                    'roi' => (float) $roi,
                ];
            }

            return null;
        });

        if ($stats === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Dashboard stats not available for your role.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

   
    public function landingPage(Request $request): JsonResponse
    {
        $cacheKey = 'landing_page_stats';

        $data = Cache::remember($cacheKey, 300, function () {

            $mosquesServed = Delivery::where('status', 'delivered')
                ->distinct('mosque_id')
                ->count('mosque_id');


            $totalDonationsAmount = Donation::sum('amount');


            $activeDonorsCount = User::where('role', 'donor')
                ->where('is_active', true)
                ->count();


            $waterDelivered = Delivery::where('status', 'delivered')
                ->sum('liters_delivered');

            return [
                'mosques_served' => $mosquesServed,
                'total_donations_amount' => (float) $totalDonationsAmount,
                'active_donors_count' => $activeDonorsCount,
                'water_delivered' => (int) $waterDelivered,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get recent activities for dashboard.
     *
     * GET /api/dashboard/activities
     *
     * Headers: Authorization: Bearer {token}
     *
     * Query Parameters:
     * - limit (integer, default: 10)
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "type": "donation",
     *       "message_ar": "تبرع جديد بقيمة 1,500 دولار من نور الشمري",
     *       "message_en": "New donation of $1,500 from Noor Al-Shammari",
     *       "created_at": "2025-01-20T10:00:00Z"
     *     }
     *   ]
     * }
     */
    public function activities(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $limit = (int) $request->get('limit', 10);
        $limit = max(1, min($limit, 100)); // Ensure limit is between 1 and 100

        $cacheKey = 'dashboard_activities_' . $limit;

        $activities = Cache::remember($cacheKey, 60, function () use ($limit) {
            return ActivityLog::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => ActivityLogResource::collection($activities),
        ], 200);
    }

    /**
     * Get donation activity chart data.
     *
     * GET /api/dashboard/donation-activity
     *
     * Headers: Authorization: Bearer {token}
     *
     * Query Parameters:
     * - period (enum: week, month, year, default: "month")
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "labels": ["Week 1", "Week 2", "Week 3", "Week 4"],
     *     "values": [5000, 7500, 6000, 8500]
     *   }
     * }
     */
    public function donationActivity(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $period = $request->get('period', 'month');

        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'month';
        }

        $labels = [];
        $values = [];

        if ($period === 'week') {
            // Last 7 days (Day 1 = today, Day 7 = 6 days ago)
            for ($i = 0; $i <= 6; $i++) {
                $date = now()->subDays($i);
                $labels[] = $i === 0 ? 'Today' : $date->format('M d');
                $values[] = (float) Donation::whereDate('created_at', $date->toDateString())
                    ->sum('amount');
            }
        } elseif ($period === 'month') {
            // Last 4 weeks (Week 1 = current week, Week 4 = oldest)
            for ($i = 0; $i <= 3; $i++) {
                $startDate = now()->subWeeks($i)->startOfWeek();
                $endDate = now()->subWeeks($i)->endOfWeek();
                $labels[] = 'Week ' . ($i + 1);
                $values[] = (float) Donation::whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
            }
        } else { // year
            // Last 12 months (Month 1 = current month, Month 12 = 11 months ago)
            for ($i = 0; $i <= 11; $i++) {
                $date = now()->subMonths($i);
                $labels[] = $date->format('M Y');
                $values[] = (float) Donation::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount');
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'values' => $values,
            ],
        ], 200);
    }

    /**
     * Get admin quick summary (Admin only).
     *
     * GET /api/dashboard/admin-summary
     *
     * Headers: Authorization: Bearer {token}
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": {
     *     "total_users": 150,
     *     "total_mosques": 45,
     *     "today_donations_count": 12,
     *     "today_donations_amount": 5000.00,
     *     "today": "2025-12-10"
     *   }
     * }
     */
    public function adminSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can access this endpoint.',
            ], 403);
        }

        $today = Carbon::today();

        $todayDonationsCount = Donation::whereDate('created_at', $today)->count();
        $todayDonationsAmount = Donation::whereDate('created_at', $today)->sum('amount');

        $cacheKey = 'admin_summary_totals';
        $totals = Cache::remember($cacheKey, 300, function () {
            return [
                'total_users' => User::count(),
                'total_mosques' => Mosque::count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totals['total_users'],
                'total_mosques' => $totals['total_mosques'],
                'today_donations_count' => $todayDonationsCount,
                'today_donations_amount' => (float) $todayDonationsAmount,
                'today' => $today->format('Y-m-d'),
            ],
        ], 200);
    }
}

