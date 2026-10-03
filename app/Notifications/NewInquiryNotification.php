<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewInquiryNotification extends Notification
{
    public function __construct(public readonly Inquiry $inquiry) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $i = $this->inquiry->loadMissing('carModel');
        $options = $i->optionNames();

        return (new MailMessage)
            ->subject("New VOLTERRA inquiry — {$i->name}")
            ->greeting('New information request')
            ->line("**{$i->name}** · {$i->email} · {$i->phone} · {$i->country}")
            ->line('Vehicle: '.($i->carModel?->name ?? $i->model))
            ->line("Configuration: {$options['exterior_color']} · {$options['wheels']} wheels · {$options['interior']} interior")
            ->line('Estimated price: €'.number_format((int) $i->estimated_price))
            ->line('Reference: VT-'.str_pad((string) $i->id, 6, '0', STR_PAD_LEFT));
    }
}
