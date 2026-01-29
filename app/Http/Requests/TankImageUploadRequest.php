<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TankImageUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only mosque_admin can upload tank images
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
            'image' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'description' => 'nullable|string',
        ];
    }
}

