<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class MosqueSupplyResource extends JsonResource
{
    /**
     * Get all products cached (to avoid N+1 queries)
     */
    private static function getProductsMap(): array
    {
        return Cache::remember('products_map', 3600, function () {
            return Product::all()->keyBy('name')->toArray();
        });
    }

    public function toArray(Request $request): array
    {
        // Get price from cached products map
        $productsMap = self::getProductsMap();
        $product = $productsMap[$this->product_type] ?? null;
        
        return [
            'id' => $this->id,
            'product_type' => $this->product_type,
            'current_quantity' => $this->current_quantity,
            'required_quantity' => $this->required_quantity,
            'need_level' => $this->need_level,
            'need_score' => $this->need_score,
            'is_active' => $this->is_active,
            'price' => $product ? (float) $product['price'] : null,
            'product_id' => $product ? (int) $product['id'] : null,
        ];
    }
}
