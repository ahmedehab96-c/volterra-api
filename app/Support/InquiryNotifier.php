<?php

namespace App\Support;

use App\Models\Inquiry;
use App\Notifications\NewInquiryNotification;
use Illuminate\Support\Facades\Notification;
use Throwable;

use function Illuminate\Support\defer;

/** Emails the configured admin about a new inquiry after the response is sent. Mail problems never fail the request. */
class InquiryNotifier
{
    public static function send(Inquiry $inquiry): void
    {
        $to = config('volterra.admin_email');
        if (! $to) {
            return;
        }

        defer(function () use ($inquiry, $to) {
            try {
                Notification::route('mail', $to)->notify(new NewInquiryNotification($inquiry));
            } catch (Throwable $e) {
                report($e); // logged; the inquiry is already saved
            }
        });
    }
}
