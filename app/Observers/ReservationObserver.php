<?php

namespace App\Observers;

use App\Http\Controllers\SignatoryApprovalController;
use App\Jobs\SendSignatoryEmailsJob;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\DirectorApprovalRequest;

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
        // Initiate the approval process
        $this->signatory_approval_controller->initiateApprovalProcess($reservation);
        // Dispatch the job to send emails simultaneously
        SendSignatoryEmailsJob::dispatch($reservation);
    }

    /**
     * Handle the Reservation "updated" event.
     */
    public function updated(Reservation $reservation): void
    {
        if ($reservation->isDirty('status') && $reservation->status === 'pending_director') {
            $director = User::where('role', 'signatory')
                ->where('position', 'school_director')
                ->first();
    
            if ($director) {
                $director->notify(new DirectorApprovalRequest($reservation));
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
