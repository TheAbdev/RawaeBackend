<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDonationWithProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'donor';
    }

    public function rules(): array
    {
        return [
            'mosque_id' => 'required|integer|exists:mosques,id',
            'donation_type' => 'required|in:amount,products',
            'amount' => 'required_if:donation_type,amount|nullable|numeric|min:1',
            'payment_method' => 'required|string|in:apple_pay,mada,stc_pay,other,system_calculated',
            'payment_transaction_id' => 'sometimes|nullable|string',
            'products' => 'required_if:donation_type,products|nullable|array|min:1',
            // Either product_id or product_type is required for each product
            'products.*.product_id' => 'nullable|integer|exists:products,id|required_without:products.*.product_type',
            'products.*.product_type' => 'nullable|string|required_without:products.*.product_id',
            'products.*.quantity' => 'required_with:products|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'donation_type.required' => 'Donation type is required (amount or products)',
            'amount.required_if' => 'Amount is required when donation type is amount',
            'payment_method.required' => 'Payment method is required',
            'payment_method.in' => 'Invalid payment method',
            'products.required_if' => 'Products are required when donation type is products',
            'products.min' => 'At least one product is required',
            'products.*.product_id.required_without' => 'Either product ID or product type is required for each product',
            'products.*.product_type.required_without' => 'Either product ID or product type is required for each product',
            'products.*.quantity.required_with' => 'Quantity is required for each product',
            'products.*.quantity.min' => 'Quantity must be at least 1',
        ];
    }
}
