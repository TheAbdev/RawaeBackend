<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class MosqueResource extends JsonResource
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

    /**
     * Calculate need level from score
     */
    private function calculateNeedLevel(int $score): string
    {
        if ($score >= 70) {
            return 'High';
        } elseif ($score >= 40) {
            return 'Medium';
        }
        return 'Low';
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Check if overall_need_score or supply_need_score was calculated in query
        // This happens when type filter is null (overall) or a specific supply type
        $effectiveNeedScore = $this->need_score; // Default: water need score

        if (isset($this->overall_need_score)) {
            // When no type filter: use the highest between water and supplies
            $effectiveNeedScore = (int) $this->overall_need_score;
        } elseif (isset($this->supply_need_score) && $this->supply_need_score !== null) {
            // When filtering by specific supply type
            $effectiveNeedScore = (int) $this->supply_need_score;
        }

        $effectiveNeedLevel = $this->calculateNeedLevel($effectiveNeedScore);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'latitude' => (string) $this->latitude,
            'longitude' => (string) $this->longitude,
            'capacity' => $this->capacity,
            'current_water_level' => $this->current_water_level,
            'required_water_level' => $this->required_water_level,
            'need_level' => $effectiveNeedLevel,
            'need_score' => $effectiveNeedScore,
            'water_need_score' => $this->need_score, // Original water-only score
            'water_need_level' => $this->need_level, // Original water-only level
            'description' => $this->description,
            'mosque_admin' => $this->whenLoaded('mosqueAdmin', function () {
                return $this->mosqueAdmin ? [
                    'id' => $this->mosqueAdmin->id,
                    'name' => $this->mosqueAdmin->name,
                    'username' => $this->mosqueAdmin->username,
                ] : null;
            }),
            'recent_donations' => $this->when(
                $this->relationLoaded('donations'),
                function () {
                    return DonationResource::collection($this->donations);
                }
            ),
            'recent_deliveries' => $this->when(
                $this->relationLoaded('deliveries'),
                function () {
                    return DeliveryResource::collection($this->deliveries);
                }
            ),


             'supplies' => $this->whenLoaded('supplies', function () {
                $productsMap = self::getProductsMap();

                // Transform supplies with cached products
                $supplies = $this->supplies->map(function ($supply) use ($productsMap) {
                    $product = $productsMap[$supply->product_type] ?? null;
                    return [
                        'id' => $supply->id,
                        'product_type' => $supply->product_type,
                        'current_quantity' => $supply->current_quantity,
                        'required_quantity' => $supply->required_quantity,
                        'need_level' => $supply->need_level,
                        'need_score' => $supply->need_score,
                        'is_active' => $supply->is_active,
                        'price' => $product ? (float) $product['price'] : null,
                        'product_id' => $product ? $product['id'] : null,
                    ];
                })->toArray();

                // Add water as a virtual supply
                $waterProduct = $productsMap['water'] ?? null;
                $waterNeedScore = $this->need_score ?? 0;

                // Calculate water need level based on need_score
                $waterNeedLevel = 'Low';
                if ($waterNeedScore >= 70) {
                    $waterNeedLevel = 'High';
                } elseif ($waterNeedScore >= 40) {
                    $waterNeedLevel = 'Medium';
                }

                $waterSupply = [
                    'id' => 0, // Virtual ID for water
                    'product_type' => 'water',
                    'current_quantity' => $this->current_water_level ?? 0,
                    'required_quantity' => $this->capacity ?? 0,
                    'need_level' => $waterNeedLevel,
                    'need_score' => $waterNeedScore,
                    'is_active' => true,
                    'price' => $waterProduct ? (float) $waterProduct['price'] : null,
                    'product_id' => $waterProduct ? $waterProduct['id'] : null,
                ];

                // Add water at the beginning of supplies array
                array_unshift($supplies, $waterSupply);

                return $supplies;
            }),


        ];
    }
}

