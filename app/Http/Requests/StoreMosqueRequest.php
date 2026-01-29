<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMosqueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin can create mosques
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'capacity' => 'required|integer|min:1',
            'required_water_level' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'mosque_admin_id' => 'required|integer|exists:users,id',
            'current_water_level' => 'nullable|integer|min:0',


            'products' => 'nullable|array',
            'products.*.product_type' => 'required|string|in:dry_food,hot_food,miswak,prayer_mat,prayer_sheets,prayer_towels,quran,quran_holder,tissues',
            'products.*.current_quantity' => 'required|integer|min:0',
            'products.*.required_quantity' => 'required|integer|min:0',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $capacity = $this->input('capacity');
            $currentWaterLevel = $this->input('current_water_level', 0);
            $requiredWaterLevel = $this->input('required_water_level');

            // current_water_level cannot be greater than capacity
            if ($currentWaterLevel > $capacity) {
                $validator->errors()->add(
                    'current_water_level',
                    'Current water level cannot be greater than capacity.'
                );
            }

            // required_water_level must be <= (capacity - current_water_level)
            $availableSpace = $capacity - $currentWaterLevel;
            if ($requiredWaterLevel > $availableSpace) {
                $validator->errors()->add(
                    'required_water_level',
                    "Required water level must be less than or equal to available space ({$availableSpace})."
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'current_water_level.max' => 'Current water level cannot exceed capacity.',
        ];
    }
}
