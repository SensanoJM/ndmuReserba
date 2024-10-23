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
        // Only initiate the approval process if the reservation is in an 'in_review' state
        if ($reservation->status === 'in_review') {
            // Dispatch the job to send emails simultaneously
            SendSignatoryEmailsJob::dispatch($reservation);
        }
    }


    /**
     * Handle the Reservation "updated" event.
     *
     * When the reservation's status is updated, this method will be called.
     * If the status is changed to 'approved' or 'denied', it will handle
     * the required actions. If the status is changed to 'pending' and the
     * previous status was 'in_review', it will send an email to the
     * school director for final approval.
     */
    public function updated(Reservation $reservation): void
    {

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
