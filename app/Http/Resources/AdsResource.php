<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdsResource extends JsonResource
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
            'title' => $this->title,
            'content' => $this->content,
            'position' => $this->position,
            'image_url' => $this->image_url,
            'link_url' => $this->link_url,
            'active' => $this->active,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}

