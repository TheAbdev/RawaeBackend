<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckLocationRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Http\Resources\TruckResource;
use App\Models\Truck;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TruckController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): JsonResponse
    {

        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Access denied.',
            ], 403);
        }

        $query = Truck::query()->with('driver');


        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }


        $perPage = min($request->get('per_page', 15), 100);
        $cacheKey = 'trucks_' . md5(json_encode($request->all()) . '_' . $perPage);

        $trucks = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => TruckResource::collection($trucks)->response()->getData(true),
        ], 200);
    }


    public function show(Request $request, $id): JsonResponse
    {

        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Access denied.',
            ], 403);
        }

        $truck = Truck::with('driver')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new TruckResource($truck),
        ], 200);
    }


    public function store(StoreTruckRequest $request): JsonResponse
    {

        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create trucks.',
            ], 403);
        }

        $truck = Truck::create([
            'truck_id' => $request->truck_id,
            'name' => $request->name,
            'capacity' => $request->capacity,
            'status' => 'active',
            'driver_name' => $request->driver_name,
            'assigned_driver_id' => $request->driver_id,
        ]);

        $truck->load('driver');


        $this->activityLogService->logOther(
            "تم إنشاء شاحنة جديدة: " . $truck->name,
            "New truck created: " . $truck->name,
            $request->user(),
            $truck->id,
            'Truck'
        );


        \App\Helpers\CacheHelper::forgetPattern('trucks_*');
        Cache::forget('dashboard_stats_*');

        return response()->json([
            'success' => true,
            'data' => new TruckResource($truck),
        ], 201);
    }


    public function update(UpdateTruckRequest $request, $id): JsonResponse
    {

        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update trucks.',
            ], 403);
        }

        $truck = Truck::findOrFail($id);

        $updateData = [];
        if ($request->has('truck_id')) {
            $updateData['truck_id'] = $request->truck_id;
        }
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('capacity')) {
            $updateData['capacity'] = $request->capacity;
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }
        if ($request->has('driver_name')) {
            $updateData['driver_name'] = $request->driver_name;
        }

        $truck->update($updateData);

        $truck->load('driver');


        $this->activityLogService->logOther(
            "تم تحديث شاحنة: " . $truck->name,
            "Truck updated: " . $truck->name,
            $request->user(),
            $truck->id,
            'Truck'
        );


        \App\Helpers\CacheHelper::forgetPattern('trucks_*');
        Cache::forget('dashboard_stats_*');

        return response()->json([
            'success' => true,
            'data' => new TruckResource($truck),
        ], 200);
    }


    public function updateLocation(UpdateTruckLocationRequest $request, $id): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $truck = Truck::findOrFail($id);


        if ($user->role === 'driver') {

            if ($truck->assigned_driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You can only update location for your assigned truck.',
                ], 403);
            }
        } elseif (!in_array($user->role, ['admin', 'logistics_supervisor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin, logistics supervisor, or assigned driver can update truck location.',
            ], 403);
        }

        $truck->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_location_update' => now(),
        ]);

        $truck->load('driver');


        $this->activityLogService->logOther(
            "تم تحديث موقع شاحنة: " . $truck->name,
            "Truck location updated: " . $truck->name,
            $user,
            $truck->id,
            'Truck'
        );


        \App\Helpers\CacheHelper::forgetPattern('trucks_*');



        return response()->json([
            'success' => true,
            'data' => new TruckResource($truck),
        ], 200);
    }


    public function destroy(Request $request, $id): JsonResponse
    {

        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete trucks.',
            ], 403);
        }

        $truck = Truck::find($id);

        if (!$truck) {
            return response()->json([
                'success' => false,
                'message' => 'Truck not found.',
            ], 404);
        }

        $truckName = $truck->name;


        $this->activityLogService->logOther(
            "تم حذف شاحنة: " . $truckName,
            "Truck deleted: " . $truckName,
            $request->user(),
            $id,
            'Truck'
        );

        $truck->delete();


        \App\Helpers\CacheHelper::forgetPattern('trucks_*');
        Cache::forget('dashboard_stats_*');

        return response()->json([
            'success' => true,
            'message' => 'Truck deleted successfully',
        ], 200);
    }

    /**
     * Get all drivers (Admin only).
     *
     * GET /api/trucks/drivers
     *
     * Response (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "سعيد السائق",
     *       "email": "driver@example.com",
     *       "phone": "0501234567",
     *       "is_active": true
     *     }
     *   ]
     * }
     */
    public function getDrivers(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can access drivers list.',
            ], 403);
        }

        $drivers = User::where('role', 'driver')
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'phone', 'is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $drivers,
        ], 200);
    }


    public function getMyTrucks(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($user->role !== 'driver') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only drivers can access their assigned trucks.',
            ], 403);
        }

        $trucks = Truck::where('assigned_driver_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TruckResource::collection($trucks),
        ], 200);
    }
}

