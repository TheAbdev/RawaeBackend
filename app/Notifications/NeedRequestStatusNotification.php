<?php

namespace App\Notifications;

use App\Models\NeedRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NeedRequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NeedRequest $needRequest,
        public string $status // 'approved' or 'rejected'
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mosque = $this->needRequest->mosque;

        $message = (new MailMessage);

        if ($this->status === 'approved') {
            $message->subject(__('Need Request Approved', [], 'en'))
                ->line(__('Your need request has been approved:', [], 'en'))
                ->line(__('Mosque: :mosque', ['mosque' => $mosque->name], 'en'))
                ->line(__('Water Quantity: :quantity liters', ['quantity' => number_format($this->needRequest->water_quantity)], 'en'));
        } else {
            $message->subject(__('Need Request Rejected', [], 'en'))
                ->line(__('Your need request has been rejected:', [], 'en'))
                ->line(__('Mosque: :mosque', ['mosque' => $mosque->name], 'en'))
                ->line(__('Water Quantity: :quantity liters', ['quantity' => number_format($this->needRequest->water_quantity)], 'en'));

            if ($this->needRequest->rejection_reason) {
                $message->line(__('Reason: :reason', ['reason' => $this->needRequest->rejection_reason], 'en'));
            }
        }

        return $message->action(__('View Request', [], 'en'), url('/admin/need-requests/' . $this->needRequest->id));
    }
}

