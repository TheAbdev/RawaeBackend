<?php

namespace App\Notifications;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Delivery $delivery
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
        $mosque = $this->delivery->mosque;

        return (new MailMessage)
            ->subject(__('Delivery Confirmed', [], 'en'))
            ->line(__('A water delivery has been confirmed:', [], 'en'))
            ->line(__('Mosque: :mosque', ['mosque' => $mosque->name], 'en'))
            ->line(__('Liters Delivered: :liters', ['liters' => number_format($this->delivery->liters_delivered)], 'en'))
            ->line(__('Delivery Date: :date', ['date' => $this->delivery->actual_delivery_date?->format('Y-m-d H:i')], 'en'))
            ->action(__('View Delivery', [], 'en'), url('/admin/deliveries/' . $this->delivery->id));
    }
}

