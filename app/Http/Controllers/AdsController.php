<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdRequest;
use App\Http\Requests\UpdateAdRequest;
use App\Http\Resources\AdsResource;
use App\Models\Ad;
use App\Services\ImageProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class AdsController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageService
    ) {
    }
    /**
     * Get list of ads.
     *
     * GET /api/ads
     *
     * Query Parameters:
     * - position (string, optional)
     * - active (boolean, optional)
     * - page (integer, default: 1)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Ad::query()->with('creator');

        // Filter by position
        if ($request->has('position') && $request->position) {
            $query->where('position', $request->position);
        }

        // Filter by active status
        if ($request->has('active') && $request->active !== null) {
            $active = filter_var($request->active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active !== null) {
                $query->where('active', $active);
            }
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 100);
        $ads = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AdsResource::collection($ads)->response()->getData(true),
        ], 200);
    }

    /**
     * Get single ad.
     *
     * GET /api/ads/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $ad = Ad::with('creator')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AdsResource($ad),
        ], 200);
    }

    /**
     * Create ad (Admin only).
     *
     * POST /api/ads
     */
    public function store(StoreAdRequest $request): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create ads.',
            ], 403);
        }

        $data = [
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'position' => $request->input('position'),
            'link_url' => $request->input('link_url'),
            'active' => $request->input('active', true),
            'created_by' => $request->user()->id,
        ];

        // Handle file upload if image is provided
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $processed = $this->imageService->processAdImage($image);

            $data['image_path'] = $processed['path'];
            $data['image_url'] = $processed['url'];
        }

        $ad = Ad::create($data);
        $ad->load('creator');

        // Clear cache
        \App\Helpers\CacheHelper::forgetPattern('ads_*');

        return response()->json([
            'success' => true,
            'data' => new AdsResource($ad),
        ], 201);
    }

    /**
     * Update ad (Admin only).
     *
     * PUT /api/ads/{id}
     */
    public function update(UpdateAdRequest $request, $id): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update ads.',
            ], 403);
        }

        $ad = Ad::findOrFail($id);

        $data = [
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'position' => $request->input('position'),
            'link_url' => $request->input('link_url'),
            'active' => $request->has('active') ? ($request->input('active', false)) : $ad->active,
        ];

        // Handle file upload if new image is provided
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($ad->image_path && Storage::disk('public')->exists($ad->image_path)) {
                Storage::disk('public')->delete($ad->image_path);
            }

            $image = $request->file('image');
            $processed = $this->imageService->processAdImage($image);

            $data['image_path'] = $processed['path'];
            $data['image_url'] = $processed['url'];
        }

        $ad->update($data);
        $ad->load('creator');

        // Clear cache
        \App\Helpers\CacheHelper::forgetPattern('ads_*');

        return response()->json([
            'success' => true,
            'data' => new AdsResource($ad),
        ], 200);
    }

    /**
     * Delete ad (Admin only).
     *
     * DELETE /api/ads/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        // Check if user is authenticated and is admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete ads.',
            ], 403);
        }

        $ad = Ad::findOrFail($id);

        // Delete the image file from storage if exists
        if ($ad->image_path && Storage::disk('public')->exists($ad->image_path)) {
            Storage::disk('public')->delete($ad->image_path);
        }

        $ad->delete();

        // Clear cache
        \App\Helpers\CacheHelper::forgetPattern('ads_*');

        return response()->json([
            'success' => true,
            'message' => 'Ad deleted successfully',
        ], 200);
    }
}

