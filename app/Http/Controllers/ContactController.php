<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{

    public function send(ContactRequest $request): JsonResponse
    {
        $firstName = $request->input('first_name');
        $lastName = $request->input('last_name');
        $email = $request->input('email');
        $subject = $request->input('subject');
        $message = $request->input('message');



        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->get();



        foreach ($admins as $admin) {
            try {
                Log::info('Preparing to send contact email', [
                    'to' => $admin->email,
                    'admin_name' => $admin->name,
                    'from' => $email,
                    'from_name' => $firstName . ' ' . $lastName,
                    'subject' => $subject ?? 'No subject provided',
                ]);

                /* Mail::to($admin->email)->send(new ContactMail(
                    $firstName,
                    $lastName,
                    $email,
                    $subject,
                    $message
                ));*/


                Log::info('Contact email processed successfully', [
                    'to' => $admin->email,
                    'admin_name' => $admin->name,
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to send contact email to admin: ' . $admin->email, [
                    'error' => $e->getMessage(),
                    'admin_name' => $admin->name,
                ]);
            }
        }



        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة بنجاح إلى جميع المديرين',
            'data' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'sent_at' => now()->toDateTimeString(),
                'recipients_count' => $admins->count(),
            ],
        ], 200);
    }
}
