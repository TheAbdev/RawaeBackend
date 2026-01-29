<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TruckResource extends JsonResource
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
            'truck_id' => $this->truck_id,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'status' => $this->status,
            //'driver_name' => $this->driver_name,
            'current_latitude' => $this->current_latitude ? (string) $this->current_latitude : null,
            'current_longitude' => $this->current_longitude ? (string) $this->current_longitude : null,
            'last_location_update' => $this->last_location_update ? $this->last_location_update->toIso8601String() : null,
            'assigned_driver' => $this->whenLoaded('driver', function () {
                return [
                    'id' => $this->driver->id,
                    'name' => $this->driver->name,
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}

