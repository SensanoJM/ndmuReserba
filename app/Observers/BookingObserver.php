<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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

            // Create base notification
            $notification = Notification::make()
                ->title($title)
                ->body("Your booking for {$booking->facility->facility_name} has been {$booking->status}.")
                ->icon($icon)
                ->iconColor($color);

            // Set URL based on user role with panel path check
            if ($booking->user->role === 'admin') {
                Log::info('Setting admin notification URL');
                $notification->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.admin.pages.tracking-page', $booking))
                ]);
            } else {
                Log::info('Setting user notification URL');
                $notification->actions([
                    \Filament\Notifications\Actions\Action::make('view')
                        ->button()
                        ->url(route('filament.user.pages.tracking-page', $booking))
                ]);
            }

            $notification->sendToDatabase($booking->user);
        }
    }

    private function notifyAdmins(Booking $booking)
    {
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
                        ->url(route('filament.admin.pages.reservation-page'))
                ])
                ->sendToDatabase($admin);
        }
    }
}