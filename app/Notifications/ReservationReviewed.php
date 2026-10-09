<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReviewed extends Notification
{
    public function __construct(public Booking $booking) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * The email telling the guest their reservation was confirmed or declined.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking;
        $stay = $booking->room->room_no.' ('.$booking->room->location->name.'), '
            .$booking->check_in->format('M d, Y').' '.$booking->timeText('check_in_time')
            .' to '.$booking->check_out->format('M d, Y').' '.$booking->timeText('check_out_time');

        if ($booking->status == 'Declined') {
            return (new MailMessage)
                ->subject('Your reservation was not approved')
                ->greeting('Hello '.$notifiable->name.',')
                ->line('We could not approve your reservation for '.$stay.'.')
                ->line('Reason: '.($booking->decline_reason ?: 'The uploaded ID could not be verified.'))
                ->action('Book again', url('/book'))
                ->line('You are welcome to send a new reservation with a clear photo of a valid ID.');
        }

        return (new MailMessage)
            ->subject('Your reservation is confirmed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Good news: your reservation is confirmed.')
            ->line($stay)
            ->action('View my reservations', url('/my-reservations'))
            ->line('Please bring the same valid ID to reception when you check in.');
    }
}
