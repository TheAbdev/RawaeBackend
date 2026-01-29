<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NeedRequestResource extends JsonResource
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
            'mosque_id' => $this->mosque_id,
            'mosque' => $this->whenLoaded('mosque', function () {
                return [
                    'id' => $this->mosque->id,
                    'name' => $this->mosque->name,
                ];
            }),
            'requested_by' => $this->whenLoaded('requester', function () {
                return [
                    'id' => $this->requester->id,
                    'name' => $this->requester->name,
                ];
            }),
            'water_quantity' => $this->water_quantity,
            'supplies' => $this->whenLoaded('supplies', function () {
                return $this->supplies->map(function ($supply) {
                    return [
                        'id' => $supply->id,
                        'product_type' => $supply->product_type,
                        'requested_quantity' => $supply->requested_quantity,
                    ];
                });
            }),
            'status' => $this->status,
            'approved_by' => $this->whenLoaded('approver', function () {
                return [
                    'id' => $this->approver->id,
                    'name' => $this->approver->name,
                ];
            }),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

