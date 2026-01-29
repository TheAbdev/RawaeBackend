<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMosqueRequest;
use App\Http\Requests\UpdateMosqueRequest;
use App\Http\Resources\MosqueResource;
use App\Models\Mosque;
use App\Models\MosqueSupply;
use App\Models\User;
use App\Services\MosqueNeedScoreService;
use App\Services\ActivityLogService;
use App\Services\MosqueSupplyNeedScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MosqueController extends Controller
{
    public function __construct(
        private MosqueNeedScoreService $needScoreService,
        private ActivityLogService $activityLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Mosque::query()->with('mosqueAdmin', 'supplies');

        $type = $request->get('type');

        $query->select('mosques.*');

        $defaultSortBy = 'need_score';

        $supplyTypes = [
            'dry_food',
            'hot_food',
            'miswak',
            'prayer_mat',
            'prayer_sheets',
            'prayer_towels',
            'quran',
            'quran_holder',
            'tissues',
        ];

        if ($type === null) {
            $query->selectRaw("
                GREATEST(
                    mosques.need_score,
                    COALESCE(
                        (SELECT MAX(need_score)
                         FROM mosque_supplies
                         WHERE mosque_supplies.mosque_id = mosques.id
                           AND mosque_supplies.is_active = 1),
                        0
                    )
                ) as overall_need_score
            ");

            $defaultSortBy = 'overall_need_score';
        } elseif ($type === 'water') {
            $defaultSortBy = 'need_score';
        } elseif (in_array($type, $supplyTypes, true)) {
            $query
                ->leftJoin('mosque_supplies as ms', function ($join) use ($type) {
                    $join->on('ms.mosque_id', '=', 'mosques.id')
                        ->where('ms.product_type', '=', $type)
                        ->where('ms.is_active', '=', true);
                })
                ->addSelect('ms.need_score as supply_need_score');

            $defaultSortBy = 'supply_need_score';
        }

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('need_level') && $request->need_level) {
            $query->where('need_level', $request->need_level);
        }

        if ($request->has('min_need_score') && $request->min_need_score !== null) {
            $query->where('need_score', '>=', $request->min_need_score);
        }

        $sortBy = $request->get('sort_by', $defaultSortBy);
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSortBy = ['need_score', 'name', 'created_at', 'overall_need_score', 'supply_need_score'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = $defaultSortBy;
        }

        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }

        $sortColumn = $sortBy === 'created_at' ? 'mosques.created_at' : $sortBy;

        $query->orderBy($sortColumn, $sortOrder);

        $perPage = min($request->get('per_page', 15), 100);
        $cacheKey = 'mosques_' . md5(json_encode($request->all()) . '_' . $perPage);

        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $mosques */
        $mosques = Cache::remember($cacheKey, 600, function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });

        $items = $mosques->items();
        $data = array_map(function ($mosque) use ($request) {
            return (new MosqueResource($mosque))->toArray($request);
        }, $items);

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $mosques->currentPage(),
            'per_page' => $mosques->perPage(),
            'total' => $mosques->total(),
            'last_page' => $mosques->lastPage(),
        ], 200);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $mosque = Mosque::with(['mosqueAdmin','supplies'])
            ->with(['donations' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->with(['deliveries' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new MosqueResource($mosque),
        ], 200);
    }

    /* public function store(StoreMosqueRequest $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create mosques.',
            ], 403);
        }

        $mosque = Mosque::create([
            'name' => $request->name,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'capacity' => $request->capacity,
            'required_water_level' => $request->required_water_level,
            'description' => $request->description,
            'current_water_level' => $request->current_water_level,
            'need_level' => 'Medium',
            'mosque_admin_id' => $request->mosque_admin_id,
            'need_score' => 0,
            'is_active' => true,
        ]);

        $this->needScoreService->updateNeedLevel($mosque);
        $mosque->refresh();

        $this->activityLogService->logMosque(
            "تم إنشاء مسجد جديد: " . $mosque->name,
            "New mosque created: " . $mosque->name,
            $request->user(),
            $mosque->id
        );

        \App\Helpers\CacheHelper::forgetPatterns(['mosques_*', 'dashboard_stats_*']);

        $mosque->load('mosqueAdmin');

        return response()->json([
            'success' => true,
            'data' => new MosqueResource($mosque),
        ], 201);
    }*/

    public function store(StoreMosqueRequest $request): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create mosques.',
            ], 403);
        }

        $mosque = Mosque::create([
            'name' => $request->name,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'capacity' => $request->capacity,
            'required_water_level' => $request->required_water_level,
            'description' => $request->description,
            'current_water_level' => $request->current_water_level ?? 0,
            'need_level' => 'Medium',
            'mosque_admin_id' => $request->mosque_admin_id,
            'need_score' => 0,
            'is_active' => true,
        ]);


        $products = $request->products ?? [
            ['product_type' => 'dry_food'],
            ['product_type' => 'hot_food'],
            ['product_type' => 'miswak'],
            ['product_type' => 'prayer_mat'],
            ['product_type' => 'prayer_sheets'],
            ['product_type' => 'prayer_towels'],
            ['product_type' => 'quran'],
            ['product_type' => 'quran_holder'],
            ['product_type' => 'tissues'],
        ];

        foreach ($products as $product) {
            MosqueSupply::create([
                'mosque_id' => $mosque->id,
                'product_type' => $product['product_type'],
                'current_quantity' => $product['current_quantity'] ?? 0,
                'required_quantity' => $product['required_quantity'] ?? 0,
                'need_level' => 'Medium',
                'need_score' => 0,
                'is_active' => true,
            ]);
        }


        $this->needScoreService->updateNeedLevel($mosque);


        app(MosqueSupplyNeedScoreService::class)->updateSuppliesNeedScores($mosque);

        $mosque->refresh();

        $this->activityLogService->logMosque(
            "تم إنشاء مسجد جديد: " . $mosque->name,
            "New mosque created: " . $mosque->name,
            $request->user(),
            $mosque->id
        );

        \App\Helpers\CacheHelper::forgetPatterns(['mosques_*', 'dashboard_stats_*']);

        $mosque->load(['mosqueAdmin', 'supplies']);

        return response()->json([
            'success' => true,
            'data' => new MosqueResource($mosque),
        ], 201);
    }


    public function update(UpdateMosqueRequest $request, $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update mosques.',
            ], 403);
        }

        $mosque = Mosque::findOrFail($id);

        $mosque->update([
            'name' => $request->name,
            'location' => $request->location,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'capacity' => $request->capacity,
            'required_water_level' => $request->required_water_level,
            'description' => $request->description,
            'mosque_admin_id' => $request->mosque_admin_id,
        ]);


        if ($request->has('products')) {
            foreach ($request->products as $productData) {

                if (isset($productData['id'])) {
                    $supply = $mosque->supplies()->find($productData['id']);
                    if ($supply) {

                        $updateData = [];

                        if (array_key_exists('current_quantity', $productData)) {
                            $updateData['current_quantity'] = $productData['current_quantity'];
                        }
                        if (array_key_exists('required_quantity', $productData)) {
                            $updateData['required_quantity'] = $productData['required_quantity'];
                        }
                        if (array_key_exists('is_active', $productData)) {
                            $updateData['is_active'] = true;//$productData['is_active'];
                        }


                        if (!empty($updateData)) {
                            $supply->update($updateData);
                        }
                    }
                }
                elseif (isset($productData['product_type'])) {
                    $mosque->supplies()->updateOrCreate(
                        [
                            'product_type' => $productData['product_type'],
                        ],
                        [
                            'current_quantity' => $productData['current_quantity'] ?? 0,
                            'required_quantity' => $productData['required_quantity'] ?? 0,
                            'is_active' => $productData['is_active'] ?? true,
                        ]
                    );
                }
            }
        }

        $this->needScoreService->updateNeedLevel($mosque);


        $mosque->load('supplies');
        app(\App\Services\MosqueSupplyNeedScoreService::class)
            ->updateSuppliesNeedScores($mosque);

        $mosque->refresh();

        $this->activityLogService->logMosque(
            "تم تحديث مسجد: " . $mosque->name,
            "Mosque updated: " . $mosque->name,
            $request->user(),
            $mosque->id
        );

        \App\Helpers\CacheHelper::forgetPatterns(['mosques_*', 'dashboard_stats_*']);

        $mosque->load('mosqueAdmin');

        return response()->json([
            'success' => true,
            'data' => new MosqueResource($mosque),
        ], 200);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete mosques.',
            ], 403);
        }

        $mosque = Mosque::findOrFail($id);
        $mosqueName = $mosque->name;
        $mosque->delete();

        $this->activityLogService->logMosque(
            "تم حذف مسجد: " . $mosqueName,
            "Mosque deleted: " . $mosqueName,
            $request->user()
        );

        \App\Helpers\CacheHelper::forgetPatterns(['mosques_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'message' => 'Mosque deleted successfully',
        ], 200);
    }

    public function getActiveMosquesCount(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can access this.',
            ], 403);
        }

        $activeCount = Mosque::where('is_active', true)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'active_mosques' => $activeCount,
                // 'inactive_mosques' => $inactiveCount,
                //  'total_mosques' => $totalCount,
            ],
        ], 200);
    }


    public function getMosqueAdmins(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can access this.',
            ], 403);
        }

        $mosqueAdmins = User::where('role', 'mosque_admin')->where('is_active', 1)
            ->select('id', 'name', 'email', 'username', 'phone', 'is_active')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $mosqueAdmins,
        ], 200);
    }
}
