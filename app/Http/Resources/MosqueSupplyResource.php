<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MosqueSupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_type' => $this->product_type,
            'current_quantity' => $this->current_quantity,
            'required_quantity' => $this->required_quantity,
            'need_level' => $this->need_level,
            'need_score' => $this->need_score,
            'is_active' => $this->is_active,
        ];
    }
}
