<?php

namespace App\Observers;

use App\Models\Signatory;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Log;
use App\Models\Reservation;

class SignatoryApprovalObserver
{
    public function sendNotificationToAdmin(Reservation $reservation): void
    {
        $admins = User::where('role', 'admin')->get();

        foreach ($admins as $admin) {
            Notification::make()
                ->title('Booking Ready for Final Approval')
                ->body("All signatories have approved booking #{$reservation->booking_id}.")
                ->sendToDatabase($admin);
        }

        Log::info("Notification sent to admin(s) for booking #{$reservation->booking_id}");
    }
}