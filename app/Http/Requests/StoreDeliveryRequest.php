<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Admin and Logistics Supervisor can create deliveries
        $user = $this->user();
        return $user && in_array($user->role, ['admin', 'logistics_supervisor']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'truck_id' => 'required|integer|exists:trucks,id',
            'mosque_id' => 'required|integer|exists:mosques,id',
            'need_request_id' => 'nullable|integer|exists:need_requests,id',
            'liters_delivered' => 'required|integer|min:1',
            'expected_delivery_date' => 'required|date',
        ];
    }
}

