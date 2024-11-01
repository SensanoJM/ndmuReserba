<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\User;
use Filament\Notifications\Notification;

class BookingObserver
{
    public function created(Booking $booking)
    {
        // Get all admin users
        $adminUsers = User::where('role', 'admin')->get();

        // Send notification to each admin
        foreach ($adminUsers as $admin) {
            Notification::make()
                ->title('New Booking Request')
                ->body("A new booking request has been submitted for {$booking->facility->facility_name}.")
                ->icon('heroicon-o-bell')
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.admin.pages.reservation-page')) // Correct Filament page route
                ])
                ->sendToDatabase($admin);
        }
    }

    public function updated(Booking $booking)
    {
        if ($booking->isDirty('status')) {
            $title = match($booking->status) {
                'approved' => 'Booking Approved',
                'denied' => 'Booking Denied',
                'in_review' => 'Booking Under Review',
                default => 'Booking Updated'
            };

            $icon = match($booking->status) {
                'approved' => 'heroicon-o-check-circle',
                'denied' => 'heroicon-o-x-circle',
                'in_review' => 'heroicon-o-clock',
                default => 'heroicon-o-bell'
            };

            $color = match($booking->status) {
                'approved' => 'success',
                'denied' => 'danger',
                'in_review' => 'warning',
                default => 'primary'
            };

            // For regular users - direct to user tracking page
            Notification::make()
                ->title($title)
                ->body("Your booking for {$booking->facility->facility_name} has been {$booking->status}.")
                ->icon($icon)
                ->iconColor($color)
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.user.pages.tracking-page')) // User tracking page
                ])
                ->sendToDatabase($booking->user);

            // If the status changes to in_review, also notify admins
            if ($booking->status === 'in_review') {
                $adminUsers = User::where('role', 'admin')->get();
                foreach ($adminUsers as $admin) {
                    Notification::make()
                        ->title('Booking Needs Review')
                        ->body("A booking for {$booking->facility->facility_name} requires review.")
                        ->icon('heroicon-o-clock')
                        ->iconColor('warning')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->button()
                                ->url(route('filament.admin.pages.reservation-page')) // Admin reservation page
                        ])
                        ->sendToDatabase($admin);
                }
            }
        }
    }
}