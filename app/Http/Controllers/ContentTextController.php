<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContentTextRequest;
use App\Http\Requests\UpdateContentTextRequest;
use App\Http\Resources\ContentTextResource;
use App\Models\ContentText;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentTextController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get all content texts.
     *
     * GET /api/content-texts
     */
    public function index(Request $request): JsonResponse
    {
        // Cache content texts (they don't change frequently)
        $data = Cache::remember('content_texts_all', 3600, function () {
            $contentTexts = ContentText::all();
            $formatted = [];
            foreach ($contentTexts as $contentText) {
                // Ensure proper serialization to match spec format exactly
                $formatted[$contentText->key] = (new ContentTextResource($contentText))->resolve();
            }
            return $formatted;
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * Get single content text by key.
     *
     * GET /api/content-texts/{key}
     */
    public function show(Request $request, string $key): JsonResponse
    {
        $contentText = ContentText::where('key', $key)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new ContentTextResource($contentText),
        ], 200);
    }

    /**
     * Create or update content text (Admin only).
     *
     * POST /api/content-texts
     */
    public function store(StoreContentTextRequest $request): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create content texts.',
            ], 403);
        }

        // Check if content text with this key already exists
        $contentText = ContentText::where('key', $request->key)->first();

        if ($contentText) {
            // Update existing
            $contentText->update([
                'value_ar' => $request->value_ar,
                'value_en' => $request->value_en,
            ]);
        } else {
            // Create new
            $contentText = ContentText::create([
                'key' => $request->key,
                'value_ar' => $request->value_ar,
                'value_en' => $request->value_en,
            ]);
        }

        // Log activity
        $this->activityLogService->logOther(
            "تم إنشاء/تحديث نص محتوى: " . $contentText->key,
            "Content text created/updated: " . $contentText->key,
            $request->user(),
            $contentText->id,
            'ContentText'
        );

        // Clear cache
        Cache::forget('content_texts_all');
        Cache::forget('content_texts_' . $contentText->key);

        return response()->json([
            'success' => true,
            'data' => new ContentTextResource($contentText),
        ], 201);
    }

    /**
     * Update content text (Admin only).
     *
     * PUT /api/content-texts/{key}
     */
    public function update(UpdateContentTextRequest $request, string $key): JsonResponse
    {
        // Check if user is admin (already checked in request authorization, but double-check)
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update content texts.',
            ], 403);
        }

        $contentText = ContentText::where('key', $key)->firstOrFail();

        $contentText->update([
            'value_ar' => $request->value_ar,
            'value_en' => $request->value_en,
        ]);

        // Log activity
        $this->activityLogService->logOther(
            "تم تحديث نص محتوى: " . $key,
            "Content text updated: " . $key,
            $request->user(),
            $contentText->id,
            'ContentText'
        );

        // Clear cache
        Cache::forget('content_texts_all');
        Cache::forget('content_texts_' . $key);

        return response()->json([
            'success' => true,
            'data' => new ContentTextResource($contentText),
        ], 200);
    }

    /**
     * Delete content text (Admin only).
     *
     * DELETE /api/content-texts/{key}
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        // Check if user is authenticated and is admin
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete content texts.',
            ], 403);
        }

        $contentText = ContentText::where('key', $key)->firstOrFail();
        $contentTextKey = $contentText->key;
        $contentText->delete();

        // Log activity
        $this->activityLogService->logOther(
            "تم حذف نص محتوى: " . $contentTextKey,
            "Content text deleted: " . $contentTextKey,
            $request->user(),
            null,
            'ContentText'
        );

        // Clear cache
        Cache::forget('content_texts_all');
        Cache::forget('content_texts_' . $contentTextKey);

        return response()->json([
            'success' => true,
            'message' => 'Content text deleted successfully',
        ], 200);
    }
}

