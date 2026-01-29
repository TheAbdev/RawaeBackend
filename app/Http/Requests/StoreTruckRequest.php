<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTruckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin can create trucks
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
            'truck_id' => 'required|string|unique:trucks,truck_id',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'driver_name' => 'nullable|string|max:255',
            'driver_id' => 'required|exists:users,id',
        ];
    }
}

