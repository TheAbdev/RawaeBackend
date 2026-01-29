<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin can update trucks
        return $this->user() && $this->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $truckId = $this->route('id');

        return [
            'truck_id' => [
                'sometimes',
                'string',
                Rule::unique('trucks', 'truck_id')->ignore($truckId),
            ],
            'name' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1',
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'inactive', 'maintenance']),
            ],
            'driver_name' => 'nullable|string|max:255',
        ];
    }
}

