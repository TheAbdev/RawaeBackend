<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only donor can create donations
        return $this->user() && $this->user()->role === 'donor';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mosque_id' => 'required|integer|exists:mosques,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:apple_pay,mada,stc_pay,other',
            'payment_transaction_id' => 'nullable|string|max:255',
        ];
    }
}

