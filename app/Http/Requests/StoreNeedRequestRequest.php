<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNeedRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only mosque_admin can create need requests
        return $this->user() && $this->user()->role === 'mosque_admin';
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
            'water_quantity' => 'nullable|integer|min:1',
            'supplies' => 'nullable|array',
            'supplies.*.product_type' => 'required_with:supplies|string|in:dry_food,hot_food,miswak,prayer_mat,prayer_sheets,prayer_towels,quran,quran_holder,tissues',
            'supplies.*.requested_quantity' => 'required_with:supplies|integer|min:1',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasWater = $this->filled('water_quantity') && $this->input('water_quantity') > 0;
            $hasSupplies = is_array($this->input('supplies')) && count($this->input('supplies')) > 0;

            if (!$hasWater && !$hasSupplies) {
                $validator->errors()->add(
                    'water_quantity',
                    'يجب إرسال كمية الماء أو قائمة المنتجات المطلوبة على الأقل.'
                );
            }
        });
    }
}

