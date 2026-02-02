<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
       if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can view products.',
            ], 403);
        }

        $products = Product::orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ], 200);
    }

    public function show(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can view products.',
            ], 403);
        }

        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can create products.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $product = Product::create($validated);
        
        // Clear products cache
        Cache::forget('products_map');

        return response()->json([
            'success' => true,
            'data' => $product,
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can update products.',
            ], 403);
        }

        $product = Product::findOrFail($id);

        $validated = $request->validate([
           // 'name' => 'sometimes|string|max:255|unique:products,name,' . $id,
            'price' => 'sometimes|nullable|numeric|min:0',
           // 'description' => 'sometimes|nullable|string',
           // 'is_active' => 'sometimes|boolean',
        ]);

        $product->update($validated);
        
        // Clear products cache
        Cache::forget('products_map');

        return response()->json([
            'success' => true,
            'data' => $product,
        ], 200);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete products.',
            ], 403);
        }

        $product = Product::findOrFail($id);
        $product->delete();
        
        // Clear products cache
        Cache::forget('products_map');

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ], 200);
    }
}
