<?php

namespace App\Http\Controllers;

use App\Http\Requests\TankImageUploadRequest;
use App\Http\Resources\TankImageResource;
use App\Models\TankImage;
use App\Models\Mosque;
use App\Services\ImageProcessingService;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class TankImageController extends Controller
{
    public function __construct(
        private ImageProcessingService $imageService,
        private ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = TankImage::with(['mosque', 'uploader']);

       
        if ($request->has('mosque_id') && $request->mosque_id) {
            $query->where('mosque_id', $request->mosque_id);
        }

        $perPage = min($request->get('per_page', 15), 100);
        $tankImages = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TankImageResource::collection($tankImages)->response()->getData(true),
        ], 200);
    }


    public function myMosque(Request $request): JsonResponse
    {

        if (!$request->user() || $request->user()->role !== 'mosque_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only mosque admins can view their mosque tank images.',
            ], 403);
        }


        $mosque = Mosque::where('mosque_admin_id', $request->user()->id)->first();

        if (!$mosque) {
            return response()->json([
                'success' => false,
                'message' => 'No mosque found for this user.',
            ], 404);
        }

        $query = TankImage::with(['mosque', 'uploader'])
            ->where('mosque_id', $mosque->id);


        $perPage = min($request->get('per_page', 15), 100);
        $tankImages = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TankImageResource::collection($tankImages)->response()->getData(true),
        ], 200);
    }


    public function store(TankImageUploadRequest $request): JsonResponse
    {

        if ($request->user()->role !== 'mosque_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only mosque admins can upload tank images.',
            ], 403);
        }


        $mosque = Mosque::findOrFail($request->mosque_id);
        if ($mosque->mosque_admin_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only upload tank images for your own mosque.',
            ], 403);
        }


        $image = $request->file('image');
        $processed = $this->imageService->processTankImage($image, $mosque->id);


        $tankImage = TankImage::create([
            'mosque_id' => $mosque->id,
            'uploaded_by' => $request->user()->id,
            'image_path' => $processed['path'],
            'image_url' => $processed['url'],
            'description' => $request->description,
        ]);


        $this->activityLogService->logOther(
            "تم رفع صورة خزان للمسجد: " . $mosque->name,
            "Tank image uploaded for mosque: " . $mosque->name,
            $request->user(),
            $tankImage->id,
            'TankImage'
        );


        \App\Helpers\CacheHelper::forgetPattern('tank_images_*');

        $tankImage->load(['mosque', 'uploader']);

        return response()->json([
            'success' => true,
            'data' => new TankImageResource($tankImage),
        ], 201);
    }


    public function destroy(Request $request, $id): JsonResponse
    {

        if (!$request->user() || $request->user()->role !== 'mosque_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only mosque admins can delete tank images.',
            ], 403);
        }

        $tankImage = TankImage::findOrFail($id);


        $mosque = Mosque::findOrFail($tankImage->mosque_id);
        if ($mosque->mosque_admin_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only delete tank images for your own mosque.',
            ], 403);
        }


        if (Storage::disk('public')->exists($tankImage->image_path)) {
            Storage::disk('public')->delete($tankImage->image_path);
        }


        $tankImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tank image deleted successfully',
        ], 200);
    }
}

