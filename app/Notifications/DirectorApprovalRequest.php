<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;
use App\Models\Reservation;
use Illuminate\Notifications\Messages\MailMessage;

class DirectorApprovalRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function via($notifiable)
    {
        return ['mail','database'];
    }

    /**
     * Get the notification's database representation.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => 'A new reservation requires your approval.',
            'reservation_id' => $this->reservation->id,
            'booking_id' => $this->reservation->booking_id,
            'user_name' => $this->reservation->booking->user->name,
            'facility_name' => $this->reservation->booking->facility->name,
        ];
    }


    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('A new reservation requires your approval.')
                    ->action('Review Reservation', url('/signatory/reservations/' . $this->reservation->id))
                    ->line('Thank you for your attention to this matter.');
    }

    public function toArray($notifiable)
    {
        return [
            'reservation_id' => $this->reservation->id,
            'message' => 'A new reservation requires your approval.',
        ];
    }
}
