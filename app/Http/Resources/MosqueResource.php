<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MosqueResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'latitude' => (string) $this->latitude,
            'longitude' => (string) $this->longitude,
            'capacity' => $this->capacity,
            'current_water_level' => $this->current_water_level,
            'required_water_level' => $this->required_water_level,
            'need_level' => $this->need_level,
            'need_score' => $this->need_score,
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
                return MosqueSupplyResource::collection($this->supplies);
            }),

           
        ];
    }
}

