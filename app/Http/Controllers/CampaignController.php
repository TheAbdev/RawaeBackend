<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Http\Resources\CampaignResource;
use App\Models\Campaign;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CampaignController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get list of campaigns.
     *
     * GET /api/campaigns
     *
     * Query Parameters:
     * - active (boolean, optional)
     * - page (integer, default: 1)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Campaign::query()->with('creator');

        // Filter by active status
        if ($request->has('active') && $request->active !== null) {
            $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active !== null) {
                $query->where('active', $active);
            }
        }

        // Pagination with caching
        $perPage = min($request->get('per_page', 15), 100);
        $cacheKey = 'campaigns_' . md5(json_encode($request->all()) . '_' . $perPage);

        $campaigns = Cache::remember($cacheKey, 600, function () use ($query, $perPage) {
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => CampaignResource::collection($campaigns)->response()->getData(true),
        ], 200);
    }

    /**
     * Get single campaign.
     *
     * GET /api/campaigns/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $campaign = Campaign::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ], 200);
    }

    /**
     * Create campaign (Admin only).
     *
     * POST /api/campaigns
     */
    public function store(StoreCampaignRequest $request): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create campaigns.',
            ], 403);
        }

        $campaign = Campaign::create([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'active' => $request->active ?? true,
            'created_by' => $request->user()->id,
        ]);

        $campaign->load('creator');

        // Log activity
        $this->activityLogService->logCampaign(
            "تم إنشاء حملة جديدة: " . $campaign->title,
            "New campaign created: " . $campaign->title,
            $request->user(),
            $campaign->id
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPattern('campaigns_*');

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ], 201);
    }

    /**
     * Update campaign (Admin only).
     *
     * PUT /api/campaigns/{id}
     */
    public function update(UpdateCampaignRequest $request, $id): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update campaigns.',
            ], 403);
        }

        $campaign = Campaign::findOrFail($id);

        $campaign->update([
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'active' => $request->active ?? $campaign->active,
        ]);

        $campaign->load('creator');

        // Log activity
        $this->activityLogService->logCampaign(
            "تم تحديث حملة: " . $campaign->title,
            "Campaign updated: " . $campaign->title,
            $request->user(),
            $campaign->id
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPattern('campaigns_*');

        return response()->json([
            'success' => true,
            'data' => new CampaignResource($campaign),
        ], 200);
    }

    /**
     * Delete campaign (Admin only).
     *
     * DELETE /api/campaigns/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        // Check if user is authenticated and is admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete campaigns.',
            ], 403);
        }

        $campaign = Campaign::findOrFail($id);
        $campaignTitle = $campaign->title;
        $campaign->delete();

        // Log activity
        $this->activityLogService->logCampaign(
            "تم حذف حملة: " . $campaignTitle,
            "Campaign deleted: " . $campaignTitle,
            $request->user()
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPattern('campaigns_*');

        return response()->json([
            'success' => true,
            'message' => 'Campaign deleted successfully',
        ], 200);
    }
}

