<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
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
            'donor' => $this->whenLoaded('donor', function () {
                return [
                    'id' => $this->donor->id,
                    'name' => $this->donor->name,
                    'email' => $this->donor->email,
                ];
            }),
            'mosque' => $this->whenLoaded('mosque', function () {
                return [
                    'id' => $this->mosque->id,
                    'name' => $this->mosque->name,
                ];
            }),
            'amount' => (string) number_format($this->amount, 2, '.', ''),
            'payment_method' => $this->payment_method,
            'payment_transaction_id' => $this->payment_transaction_id,
            'status' => $this->status,
            'verified' => $this->verified,
            'verified_by' => $this->whenLoaded('verifier', function () {
                return [
                    'id' => $this->verifier->id,
                    'name' => $this->verifier->name,
                ];
            }),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

