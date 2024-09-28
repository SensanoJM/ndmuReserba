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
        $signatories = $this->reservation->signatories()->with('user')->get();

        foreach ($signatories as $signatory) {
            if ($signatory->user) {
                Mail::to($signatory->user->email)
                    ->send(new SignatoryApprovalRequest($this->reservation, $signatory));
            }
        }
    }
}
