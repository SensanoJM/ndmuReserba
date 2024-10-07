<?php

namespace App\Observers;

use App\Http\Controllers\SignatoryApprovalController;
use App\Jobs\SendSignatoryEmailsJob;
use App\Mail\DirectorApprovalRequest;
use App\Models\Reservation;
use Illuminate\Support\Facades\Mail;

class ReservationObserver
{
    protected $signatory_approval_controller;

    public function __construct(SignatoryApprovalController $signatory_approval_controller)
    {
        $this->signatory_approval_controller = $signatory_approval_controller;
    }

    /**
     * Handle the Reservation "created" event.
     */
    public function created(Reservation $reservation): void
    {
        // Only initiate the approval process if the reservation is in a 'pending' state
        if ($reservation->status === 'pending') {
            // Initiate the approval process
            $this->signatory_approval_controller->initiateApprovalProcess($reservation);
            // Dispatch the job to send emails simultaneously
            SendSignatoryEmailsJob::dispatch($reservation);
        }
    }

    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        if ($reservation->isDirty('status')) {
            if ($reservation->status === 'approved') {
                // Handle final approval if needed
                // For example, you might want to notify the user or update related records
            } elseif ($reservation->status === 'denied') {
                // Handle denial if needed
                // For example, you might want to notify the user or update related records
            }
        }
    }

    /**
     * Handle the Reservation "deleted" event.
     */
    public function deleted(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "restored" event.
     */
    public function restored(Reservation $reservation): void
    {
        //
    }

    /**
     * Handle the Reservation "force deleted" event.
     */
    public function forceDeleted(Reservation $reservation): void
    {
        //
    }
}
