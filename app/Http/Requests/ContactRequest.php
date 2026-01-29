<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'الاسم الأول مطلوب',
            'first_name.string' => 'الاسم الأول يجب أن يكون نص',
            'first_name.max' => 'الاسم الأول يجب ألا يتجاوز 100 حرف',
            'last_name.required' => 'الاسم الأخير مطلوب',
            'last_name.string' => 'الاسم الأخير يجب أن يكون نص',
            'last_name.max' => 'الاسم الأخير يجب ألا يتجاوز 100 حرف',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرف',
            'subject.string' => 'الموضوع يجب أن يكون نص',
            'subject.max' => 'الموضوع يجب ألا يتجاوز 255 حرف',
            'message.required' => 'الرسالة مطلوبة',
            'message.string' => 'الرسالة يجب أن تكون نص',
            'message.max' => 'الرسالة يجب ألا تتجاوز 5000 حرف',
        ];
    }
}
