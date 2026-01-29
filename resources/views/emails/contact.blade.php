@component('mail::message')
# رسالة تواصل جديدة

**الاسم:** {{ $firstName }} {{ $lastName }}

**البريد الإلكتروني:** {{ $email }}

@if($subject)
**الموضوع:** {{ $subject }}
@endif

**الرسالة:**

{{ $message }}

---

هذه الرسالة تم إرسالها من صفحة التواصل في منصة رواء الحرم.

@component('mail::button', ['url' => config('app.url')])
زيارة المنصة
@endcomponent

شكراً،<br>
{{ config('app.name') }}
@endcomponent
