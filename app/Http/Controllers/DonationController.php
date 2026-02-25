<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonationRequest;
use App\Http\Requests\StoreDonationWithProductsRequest;
use App\Http\Requests\UpdateDonationStatusRequest;
use App\Http\Requests\VerifyDonationRequest;
use App\Http\Resources\DonationResource;
use App\Models\Donation;
use App\Models\DonationItem;
use App\Models\Mosque;
use App\Models\MosqueSupply;
use App\Models\Product;
use App\Services\ActivityLogService;
use App\Services\MosqueNeedScoreService;
use App\Services\MosqueSupplyNeedScoreService;
use App\Notifications\NewDonationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DonationController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Donation::with(['donor', 'mosque', 'verifier']);


        if ($request->has('donor_id') && $request->donor_id) {
            $query->where('donor_id', $request->donor_id);
        }


        if ($request->has('mosque_id') && $request->mosque_id) {
            $query->where('mosque_id', $request->mosque_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $user = $request->user();
        if ($user && in_array($user->role, ['donor'])) {
            $query->where('donor_id', $user->id);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $cacheKey = 'donations_' . md5(json_encode($request->all()) . '_' . $perPage . '_' . ($user?->id ?? 'guest'));

        $donations = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => DonationResource::collection($donations)->response()->getData(true),
        ], 200);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $donation = Donation::with(['donor', 'mosque', 'verifier'])->findOrFail($id);

        $user = $request->user();
        if ($user && in_array($user->role, ['donor'])) {
            if ($donation->donor_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You can only view your own donations.',
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => new DonationResource($donation),
        ], 200);
    }

    public function store(StoreDonationRequest $request): JsonResponse
    {
        if ($request->user()->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only donors can create donations.',
            ], 403);
        }

        $donation = Donation::create([
            'donor_id' => $request->user()->id,
            'mosque_id' => $request->mosque_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_transaction_id' => $request->payment_transaction_id,
            'status' => 'pending',
            'verified' => false,
        ]);

        $paymentResult = $this->processMockPayment($donation);

        if ($paymentResult['success']) {
            $donation->update([
                'status' => 'completed',
                'payment_transaction_id' => $paymentResult['transaction_id'],
            ]);
        } else {
            $donation->update([
                'status' => 'failed',
            ]);
        }

        $donation->refresh();
        $donation->load(['donor', 'mosque']);

        $this->activityLogService->logDonation(
            "تبرع جديد بقيمة " . number_format($donation->amount, 2) . " ريال من " . $donation->donor->name . " لمسجد " . $donation->mosque->name,
            "New donation of " . number_format($donation->amount, 2) . " SAR from " . $donation->donor->name . " to " . $donation->mosque->name,
            $request->user(),
            $donation->id,
            ['amount' => $donation->amount, 'status' => $donation->status]
        );

        $admins = \App\Models\User::whereIn('role', ['admin', 'auditor'])->get();
       /* if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewDonationNotification($donation));
        }*/

        \App\Helpers\CacheHelper::forgetPatterns(['donations_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $donation->id,
                'donor_id' => $donation->donor_id,
                'mosque_id' => $donation->mosque_id,
                'amount' => number_format($donation->amount, 2, '.', ''),
                'payment_method' => $donation->payment_method,
                'status' => $donation->status,
                'created_at' => $donation->created_at->toIso8601String(),
            ],
        ], 201);
    }

    public function storeWithProducts(StoreDonationWithProductsRequest $request): JsonResponse
    {
        if ($request->user()->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only donors can create donations.',
            ], 403);
        }

        $donationType = $request->donation_type;

        if ($donationType === 'products') {
            $products = $request->products;
            $location = $request->location;
            $mosqueId = $request->get('mosque_id');
            $mosque = $mosqueId ? Mosque::find($mosqueId) : null;
            $totalPrice = 0;
            $donationItems = [];
            $suppliesUpdates = [];

            foreach ($products as $item) {
                // Support both product_id and product_type
                $product = null;
                if (isset($item['product_id'])) {
                    $product = Product::findOrFail($item['product_id']);
                } elseif (isset($item['product_type'])) {
                    $product = Product::where('name', $item['product_type'])->first();
                    if (!$product) {
                        return response()->json([
                            'success' => false,
                            'message' => "Product type '{$item['product_type']}' not found.",
                        ], 400);
                    }
                }

                if (!$product) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product ID or product type is required.',
                    ], 400);
                }

                if (is_null($product->price)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Product '{$product->name}' has no price set yet.",
                    ], 400);
                }

                $itemTotal = $product->price * $item['quantity'];
                $totalPrice += $itemTotal;

                $donationItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $itemTotal,
                ];

                // Track supply updates
                $suppliesUpdates[] = [
                    'product_type' => $product->name,
                    'quantity' => $item['quantity'],
                ];
            }

            // Create donation with pending status first
            $donation = Donation::create([
                'donor_id' => $request->user()->id,
                'mosque_id' => $mosqueId,
                'location' => $location,
                'amount' => $totalPrice,
                'donation_type' => 'products',
                'payment_method' => $request->payment_method ?? 'system_calculated',
                'status' => 'pending',
                'verified' => false,
            ]);

            // Process payment
            $paymentResult = $this->processMockPayment($donation);

            if ($paymentResult['success']) {
                // Update donation status to completed
                $donation->update([
                    'status' => 'completed',
                    'payment_transaction_id' => $paymentResult['transaction_id'],
                ]);

                // Create donation items
                foreach ($donationItems as $item) {
                    DonationItem::create([
                        'donation_id' => $donation->id,
                        ...$item,
                    ]);
                }

                // Update mosque supplies and water level only when donation is for a specific mosque
                $waterDonated = false;
                if ($mosqueId) {
                    DB::transaction(function () use ($suppliesUpdates, $mosqueId, &$waterDonated) {
                        foreach ($suppliesUpdates as $update) {
                            if ($update['product_type'] === 'water') {

                                Mosque::where('id', $mosqueId)
                                    ->increment('current_water_level', $update['quantity']);
                                $mosque = Mosque::where('id', $mosqueId)->first();
                                $mosque->required_water_level -= $update['quantity'];
                                $mosque->save();
                                $waterDonated = true;
                            } else {
                                MosqueSupply::where('mosque_id', $mosqueId)
                                    ->where('product_type', $update['product_type'])
                                    ->increment('current_quantity', $update['quantity']);
                            }
                        }
                    });
                }

                if ($mosqueId && $mosque) {
                    $mosque->refresh();
                    $mosque->load('supplies');

                    if ($waterDonated) {
                        app(MosqueNeedScoreService::class)->updateNeedLevel($mosque);
                    }
                    app(MosqueSupplyNeedScoreService::class)->updateSuppliesNeedScores($mosque);
                }

                $donation->refresh();
                $donation->load(['donor', 'mosque', 'items.product']);

                $logMessageAr = $mosque
                    ? "تبرع بمنتجات بقيمة " . number_format($donation->amount, 2) . " ريال من " . $donation->donor->name . " لمسجد " . $donation->mosque->name
                    : "تبرع بمنتجات بقيمة " . number_format($donation->amount, 2) . " ريال من " . $donation->donor->name . " للموقع: " . ($donation->location ?? '');
                $logMessageEn = $mosque
                    ? "Product donation of " . number_format($donation->amount, 2) . " SAR from " . $donation->donor->name . " to " . $donation->mosque->name
                    : "Product donation of " . number_format($donation->amount, 2) . " SAR from " . $donation->donor->name . " to location: " . ($donation->location ?? '');

                $this->activityLogService->logDonation(
                    $logMessageAr,
                    $logMessageEn,
                    $request->user(),
                    $donation->id,
                    ['amount' => $donation->amount, 'status' => $donation->status, 'items_count' => count($donationItems)]
                );

                \App\Helpers\CacheHelper::forgetPatterns(['donations_*', 'dashboard_stats_*', 'mosques_*']);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'id' => $donation->id,
                        'donor_id' => $donation->donor_id,
                        'mosque_id' => $donation->mosque_id,
                        'location' => $donation->location,
                        'donation_type' => $donation->donation_type,
                        'amount' => number_format($donation->amount, 2, '.', ''),
                        'payment_method' => $donation->payment_method,
                        'status' => $donation->status,
                        'items_count' => count($donationItems),
                        'items' => $donationItems,
                        'created_at' => $donation->created_at->toIso8601String(),
                    ],
                ], 201);
            } else {
                // Payment failed
                $donation->update([
                    'status' => 'failed',
                ]);

                $donation->refresh();
                $donation->load(['donor', 'mosque']);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed. Please try again.',
                    'data' => [
                        'id' => $donation->id,
                        'status' => $donation->status,
                    ],
                ], 400);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid donation type',
        ], 400);
    }

    public function myHistory(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'donor') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only donors can view their donation history.',
            ], 403);
        }

        $query = Donation::with(['donor', 'mosque', 'verifier'])
            ->where('donor_id', $request->user()->id);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $donations = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DonationResource::collection($donations)->response()->getData(true),
        ], 200);
    }

    public function verify(VerifyDonationRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'auditor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin or auditor can verify donations.',
            ], 403);
        }

        $donation = Donation::findOrFail($id);
        if($donation->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => 'Donation can not be verified because it is failed try change the status first.',
            ], 400);
        }

        $donation->update([
            'verified' => $request->verified,
            'verified_by' => $request->verified ? $user->id : null,
            'verified_at' => $request->verified ? now() : null,
        ]);

        $donation->load(['donor', 'mosque', 'verifier']);

        $status = $request->verified ? 'تم التحقق من' : 'تم إلغاء التحقق من';
        $this->activityLogService->logDonation(
            $status . " تبرع بقيمة " . number_format($donation->amount, 2) . " ريال",
            ($request->verified ? 'Verified' : 'Unverified') . " donation of " . number_format($donation->amount, 2) . " SAR",
            $user,
            $donation->id
        );

        // Clear cache
        \App\Helpers\CacheHelper::forgetPatterns(['donations_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new DonationResource($donation),
        ], 200);
    }


    public function updateStatus(UpdateDonationStatusRequest $request, $id): JsonResponse
    {

        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update donation status.',
            ], 403);
        }

        $donation = Donation::findOrFail($id);

        $donation->update([
            'status' => $request->status,
        ]);

        $donation->load(['donor', 'mosque', 'verifier']);

        $this->activityLogService->logDonation(
            "تم تحديث حالة التبرع إلى: " . $request->status,
            "Donation status updated to: " . $request->status,
            $request->user(),
            $donation->id
        );

        \App\Helpers\CacheHelper::forgetPatterns(['donations_*', 'dashboard_stats_*']);

        return response()->json([
            'success' => true,
            'data' => new DonationResource($donation),
        ], 200);
    }


    private function processMockPayment(Donation $donation): array
    {

        $success = rand(1, 100) <= 90;

        if ($success) {
            return [
                'success' => true,
                'transaction_id' => $donation->payment_transaction_id ?: 'TXN' . strtoupper(uniqid()),
                'amount' => $donation->amount,
                'payment_method' => $donation->payment_method,
                'status' => 'completed',
            ];
        } else {
            return [
                'success' => false,
                'status' => 'failed',
            ];
        }
    }


    public function getStats(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can access this.',
            ], 403);
        }

        $completedCount = Donation::where('status', 'completed')->count();
        $pendingCount = Donation::where('status', 'pending')->count();
        $failedCount = Donation::where('status', 'failed')->count();
        $cancelledCount = Donation::where('status', 'cancelled')->count();
        $totalCount = Donation::count();
        $totalAmount = Donation::where('status', 'completed')->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
              //  'completed_donations' => $completedCount,
              //  'pending_donations' => $pendingCount,
              //  'failed_donations' => $failedCount,
              //  'cancelled_donations' => $cancelledCount,
                'total_donations' => $totalCount,
                'total_amount' => round($totalAmount, 2),
            ],
        ], 200);
    }
}

