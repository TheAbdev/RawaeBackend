<?php

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewDonationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation
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
        $mosque = $this->donation->mosque;
        $donor = $this->donation->donor;

        return (new MailMessage)
            ->subject(__('New Donation Received', [], 'en'))
            ->line(__('A new donation has been received:', [], 'en'))
            ->line(__('Amount: :amount', ['amount' => number_format($this->donation->amount, 2) . ' SAR'], 'en'))
            ->line(__('Mosque: :mosque', ['mosque' => $mosque->name], 'en'))
            ->line(__('Donor: :donor', ['donor' => $donor->name], 'en'))
            ->line(__('Payment Method: :method', ['method' => $this->donation->payment_method], 'en'))
            ->action(__('View Donation', [], 'en'), url('/admin/donations/' . $this->donation->id));
    }
}

