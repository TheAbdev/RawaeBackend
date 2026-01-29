<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Requests\UpdateDeliveryStatusRequest;
use App\Http\Requests\UploadDeliveryProofRequest;
use App\Http\Resources\DeliveryResource;
use App\Models\Delivery;
use App\Services\ImageProcessingService;
use App\Services\ActivityLogService;
use App\Services\MosqueNeedScoreService;
use App\Notifications\DeliveryConfirmationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class DeliveryController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageService,
        private ActivityLogService $activityLogService,
        private MosqueNeedScoreService $needScoreService
    ) {
    }
    /**
     * Get list of deliveries.
     *
     * GET /api/deliveries
     *
     * Query Parameters:
     * - truck_id (integer, optional)
     * - mosque_id (integer, optional)
     * - status (enum: pending, in-transit, delivered, cancelled, optional)
     * - page (integer, default: 1)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Delivery::query()->with(['truck', 'mosque', 'deliverer']);

        // Filter by truck_id
        if ($request->has('truck_id') && $request->truck_id) {
            $query->where('truck_id', $request->truck_id);
        }

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
        $deliveries = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DeliveryResource::collection($deliveries)->response()->getData(true),
        ], 200);
    }

    /**
     * Get single delivery details.
     *
     * GET /api/deliveries/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $delivery = Delivery::with(['truck', 'mosque', 'deliverer', 'needRequest'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new DeliveryResource($delivery),
        ], 200);
    }

    /**
     * Create new delivery (Admin/Logistics Supervisor only).
     *
     * POST /api/deliveries
     */
    public function store(StoreDeliveryRequest $request): JsonResponse
    {
        // Check if user is admin or logistics_supervisor (already checked in request authorization, but double-check)
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or logistics supervisor can create deliveries.',
            ], 403);
        }

        $delivery = Delivery::create([
            'truck_id' => $request->truck_id,
            'mosque_id' => $request->mosque_id,
            'need_request_id' => $request->need_request_id,
            'liters_delivered' => $request->liters_delivered,
            'expected_delivery_date' => $request->expected_delivery_date,
            'status' => 'pending',
        ]);

        $delivery->load(['truck', 'mosque', 'deliverer']);

        // Log activity
        $this->activityLogService->logDelivery(
            "تم إنشاء تسليم جديد للمسجد: " . $delivery->mosque->name,
            "New delivery created for mosque: " . $delivery->mosque->name,
            $user,
            $delivery->id
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPatterns(['deliveries_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new DeliveryResource($delivery),
        ], 201);
    }

    /**
     * Update delivery status.
     *
     * PUT /api/deliveries/{id}/status
     */
    public function updateStatus(UpdateDeliveryStatusRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // Check role-based access: Admin and Logistics Supervisor can update status
        if (!in_array($user->role, ['admin', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or logistics supervisor can update delivery status.',
            ], 403);
        }

        $delivery = Delivery::findOrFail($id);
        $newStatus = $request->status;

        // Update status
        $updateData = ['status' => $newStatus];

        // If status is 'delivered', set actual_delivery_date and delivered_by
        if ($newStatus === 'delivered') {
            $updateData['actual_delivery_date'] = now();
            $updateData['delivered_by'] = $user->id;
        }

        $delivery->update($updateData);
        $delivery->load(['truck', 'mosque', 'deliverer']);

        // If delivered, update mosque water level and need score
        if ($newStatus === 'delivered') {
            $mosque = $delivery->mosque;
            $mosque->increment('current_water_level', $delivery->liters_delivered);

            // Recalculate need score
            $this->needScoreService->updateNeedLevel($mosque);

            // Send email notification
           /* if ($mosque->mosqueAdmin) {
                $mosque->mosqueAdmin->notify(new DeliveryConfirmationNotification($delivery));
            }*/

            // Log activity
            $this->activityLogService->logDelivery(
                "تم تسليم " . number_format($delivery->liters_delivered) . " لتر للمسجد: " . $mosque->name,
                "Delivered " . number_format($delivery->liters_delivered) . " liters to mosque: " . $mosque->name,
                $user,
                $delivery->id
            );
        } else {
            // Log activity for status change
            $this->activityLogService->logDelivery(
                "تم تحديث حالة التسليم إلى: " . $newStatus,
                "Delivery status updated to: " . $newStatus,
                $user,
                $delivery->id
            );
        }

        // Clear cache
        \App\Helpers\CacheHelper::forgetPatterns(['deliveries_*', 'mosques_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new DeliveryResource($delivery),
        ], 200);
    }

    /**
     * Upload delivery proof image.
     *
     * POST /api/deliveries/{id}/proof
     */
    public function uploadProof(UploadDeliveryProofRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // Check role-based access: Admin and Logistics Supervisor can upload proof
        if (!in_array($user->role, ['admin', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or logistics supervisor can upload delivery proof.',
            ], 403);
        }

        $delivery = Delivery::findOrFail($id);

        // Process and store image with optimization
        $image = $request->file('image');
        $processed = $this->imageService->processDeliveryProof($image, $delivery->id);

        // Update delivery with proof image and optional location/notes
        $updateData = [
            'proof_image_path' => $processed['path'],
            'proof_image_url' => $processed['url'],
        ];

        if ($request->has('delivery_latitude')) {
            $updateData['delivery_latitude'] = $request->delivery_latitude;
        }

        if ($request->has('delivery_longitude')) {
            $updateData['delivery_longitude'] = $request->delivery_longitude;
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        $delivery->update($updateData);
        $delivery->load(['truck', 'mosque', 'deliverer']);

        return response()->json([
            'success' => true,
            'data' => new DeliveryResource($delivery),
        ], 200);
    }
}

