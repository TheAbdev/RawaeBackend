<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
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
            'truck' => $this->whenLoaded('truck', function () {
                return [
                    'id' => $this->truck->id,
                    'truck_id' => $this->truck->truck_id,
                    'name' => $this->truck->name,
                ];
            }),
            'mosque' => $this->whenLoaded('mosque', function () {
                return [
                    'id' => $this->mosque->id,
                    'name' => $this->mosque->name,
                ];
            }),
            'liters_delivered' => $this->liters_delivered,
            'proof_image_url' => $this->proof_image_url,
            'status' => $this->status,
            'expected_delivery_date' => $this->expected_delivery_date ? $this->expected_delivery_date->format('Y-m-d') : null,
            'actual_delivery_date' => $this->actual_delivery_date ? $this->actual_delivery_date->toIso8601String() : null,
            'delivered_by' => $this->whenLoaded('deliverer', function () {
                return [
                    'id' => $this->deliverer->id,
                    'name' => $this->deliverer->name,
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}

