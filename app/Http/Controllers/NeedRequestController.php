<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNeedRequestRequest;
use App\Http\Requests\RejectNeedRequestRequest;
use App\Http\Resources\NeedRequestResource;
use App\Models\NeedRequest;
use App\Models\Mosque;
use App\Models\NeedRequestSupply;
use App\Services\ActivityLogService;
use App\Notifications\NeedRequestStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NeedRequestController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get list of need requests.
     *
     * GET /api/need-requests
     *
     * Query Parameters:
     * - mosque_id (integer, optional)
     * - status (enum: pending, approved, rejected, fulfilled, optional)
     * - page (integer, default: 1)
     */
    public function index(Request $request): JsonResponse
    {
        $query = NeedRequest::with(['mosque', 'requester', 'approver', 'supplies']);

        // Filter by mosque_id
        if ($request->has('mosque_id') && $request->mosque_id) {
            $query->where('mosque_id', $request->mosque_id);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $needRequests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => NeedRequestResource::collection($needRequests)->response()->getData(true),
        ], 200);
    }

    /**
     * Get need requests for current user's mosque (Mosque Admin only).
     *
     * GET /api/need-requests/my-mosque
     */
    public function myMosque(Request $request): JsonResponse
    {
        // Check if user is authenticated and is mosque_admin
        if (!$request->user() || $request->user()->role !== 'mosque_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only mosque admins can view their mosque need requests.',
            ], 403);
        }

        // Get the mosque associated with the current user
        $mosque = Mosque::where('mosque_admin_id', $request->user()->id)->first();

        if (!$mosque) {
            return response()->json([
                'success' => false,
                'message' => 'No mosque found for this user.',
            ], 404);
        }

        $query = NeedRequest::with(['mosque', 'requester', 'approver', 'supplies'])
            ->where('mosque_id', $mosque->id);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $needRequests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => NeedRequestResource::collection($needRequests)->response()->getData(true),
        ], 200);
    }

    /**
     * Get need requests for a specific mosque (Admin/Logistics Supervisor only).
     *
     * GET /api/need-requests/mosque/{mosqueId}
     */
    public function getByMosque(Request $request, $mosqueId): JsonResponse
    {
        // Check if user is admin or logistics_supervisor
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or logistics supervisor can access this.',
            ], 403);
        }

        // Check if mosque exists
        $mosque = Mosque::find($mosqueId);
        if (!$mosque) {
            return response()->json([
                'success' => false,
                'message' => 'Mosque not found.',
            ], 404);
        }

        $query = NeedRequest::with(['mosque', 'requester', 'approver', 'supplies'])
            ->where('mosque_id', $mosqueId)->where('status','approved');

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $needRequests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => NeedRequestResource::collection($needRequests)->response()->getData(true),
        ], 200);
    }

    /**
     * Create new need request (Mosque Admin only).
     *
     * POST /api/need-requests
     */
    public function store(StoreNeedRequestRequest $request): JsonResponse
    {
        // Check if user is mosque_admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'mosque_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only mosque admins can create need requests.',
            ], 403);
        }

        // Verify that the mosque belongs to the current user
        $mosque = Mosque::findOrFail($request->mosque_id);
        if ($mosque->mosque_admin_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only create need requests for your own mosque.',
            ], 403);
        }

        $needRequest = NeedRequest::create([
            'mosque_id' => $request->mosque_id,
            'requested_by' => $request->user()->id,
            'water_quantity' => $request->water_quantity,
            'status' => 'pending',
        ]);


        if ($request->has('supplies')) {
            foreach ($request->supplies as $item) {
                $needRequest->supplies()->create([
                    'product_type' => $item['product_type'],
                    'requested_quantity' => $item['requested_quantity'],
                ]);
            }
        }

        $needRequest->load(['mosque', 'requester', 'approver', 'supplies']);


        $waterPartAr = $needRequest->water_quantity
            ? " بكمية " . number_format($needRequest->water_quantity) . " لتر"
            : "";

        $waterPartEn = $needRequest->water_quantity
            ? " with " . number_format($needRequest->water_quantity) . " liters"
            : "";

        $suppliesCount = $needRequest->supplies->count();

        $suppliesPartAr = $suppliesCount > 0
            ? " + منتجات بعدد أنواع: " . $suppliesCount
            : "";

        $suppliesPartEn = $suppliesCount > 0
            ? " + supplies types: " . $suppliesCount
            : "";

        $this->activityLogService->logOther(
            "تم إنشاء طلب حاجة جديد للمسجد: " . $needRequest->mosque->name . $waterPartAr . $suppliesPartAr,
            "New need request created for mosque: " . $needRequest->mosque->name . $waterPartEn . $suppliesPartEn,
            $request->user(),
            $needRequest->id,
            'NeedRequest'
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPatterns(['need_requests_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new NeedRequestResource($needRequest),
        ], 201);
    }

    /**
     * Approve need request (Admin only).
     *
     * PUT /api/need-requests/{id}/approve
     */
    public function approve(Request $request, $id): JsonResponse
    {
        // Check if user is admin (already checked via middleware, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can approve need requests.',
            ], 403);
        }

        $needRequest = NeedRequest::findOrFail($id);

        // State machine: Only pending requests can be approved
        if ($needRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending need requests can be approved.',
            ], 400);
        }

        // Update status to approved
        $needRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null, // Clear rejection reason if it was set
        ]);

        $needRequest->load(['mosque', 'requester', 'approver']);

        // Send email notification to mosque admin
      /*  if ($needRequest->mosque->mosqueAdmin) {
            $needRequest->mosque->mosqueAdmin->notify(new NeedRequestStatusNotification($needRequest, 'approved'));
        }*/

        // Log activity
        $this->activityLogService->logOther(
            "تم الموافقة على طلب حاجة للمسجد: " . $needRequest->mosque->name,
            "Need request approved for mosque: " . $needRequest->mosque->name,
            $request->user(),
            $needRequest->id,
            'NeedRequest'
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPatterns(['need_requests_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new NeedRequestResource($needRequest),
        ], 200);
    }

    /**
     * Reject need request (Admin only).
     *
     * PUT /api/need-requests/{id}/reject
     */
    public function reject(RejectNeedRequestRequest $request, $id): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can reject need requests.',
            ], 403);
        }

        $needRequest = NeedRequest::findOrFail($id);

        // State machine: Only pending requests can be rejected
        if ($needRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending need requests can be rejected.',
            ], 400);
        }

        // Update status to rejected
        $needRequest->update([
            'status' => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => $request->rejection_reason,
        ]);

        $needRequest->load(['mosque', 'requester', 'approver']);

        // Send email notification to mosque admin
       /* if ($needRequest->mosque->mosqueAdmin) {
            $needRequest->mosque->mosqueAdmin->notify(new NeedRequestStatusNotification($needRequest, 'rejected'));
        }*/

        // Log activity
        $this->activityLogService->logOther(
            "تم رفض طلب حاجة للمسجد: " . $needRequest->mosque->name,
            "Need request rejected for mosque: " . $needRequest->mosque->name,
            $request->user(),
            $needRequest->id,
            'NeedRequest'
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPatterns(['need_requests_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new NeedRequestResource($needRequest),
        ], 200);
    }
}

