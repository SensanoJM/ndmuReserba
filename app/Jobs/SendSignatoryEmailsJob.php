<?php

namespace App\Jobs;

use App\Models\Reservation;
use App\Mail\SignatoryApprovalRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\DirectorApprovalRequest;

class SendSignatoryEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $reservation;
    
    /**
     * Create a new job instance.
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $signatories = $this->reservation->signatories;

        foreach ($signatories as $signatory) {
            if ($signatory->role === 'school_director') {
                continue; // Skip director for now
            }

            $email = $this->getSignatoryEmail($signatory);
            
            if ($email) {
                Mail::to($email)->send(new SignatoryApprovalRequest($this->reservation, $signatory));
            }
        }
    }

    /**
     * Gets the email address of the given signatory. If the signatory has an email
     * address set, that will be returned. Otherwise, the email address of the
     * signatory's user will be returned if the user exists, or null if the user
     * does not exist.
     */
    private function getSignatoryEmail($signatory)
    {
        return $signatory->email ?? optional($signatory->user)->email;
    }

    /**
     * Checks if all non-director signatories have approved the given reservation.
     * If yes, sends an email to the director signatory, requesting final approval.
     * If the director exists and has an email address, the email is sent using the
     * DirectorApprovalRequest mailable class. The reservation status is updated
     * to 'pending_director' to reflect that it is waiting for director approval.
     */
    private function checkAndSendDirectorApproval()
    {
        if ($this->reservation->allNonDirectorSignatoriesApproved()) {
            $director = $this->reservation->signatories()->where('role', 'school_director')->first();
            if ($director) {
                $email = $this->getSignatoryEmail($director);
                if ($email) {
                    Mail::to($email)->send(new DirectorApprovalRequest($this->reservation, $director));
                    $this->reservation->update(['status' => 'pending_director']);
                }
            }
        }
    }
}
