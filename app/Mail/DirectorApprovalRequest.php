<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Support\Str;

class DirectorApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $director;
    public $approvalUrl;
    public $denialUrl;
    public $previousApprovals;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, Signatory $director)
    {
        $this->reservation = $reservation;
        $this->director = $director;
    
        if (!$this->director->approval_token) {
            $this->director->approval_token = Str::random(32);
            $this->director->save();
        }
    
        $this->approvalUrl = $director->approval_url;
        $this->denialUrl = $director->deny_url;
        
        $this->previousApprovals = $this->getPreviousApprovals();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    
    public function build()
    {
        return $this->view('emails.director-approval-request')
                    ->subject('Final Approval Request for Reservation')
                    ->with([
                        'approvalUrl' => $this->approvalUrl,
                        'denialUrl' => $this->denialUrl,
                        'previousApprovals' => $this->previousApprovals
                    ]);
    }

    /**
     * Gets the list of previous approvals of the given reservation.
     * 
     * @return \Illuminate\Support\Collection|array
     */
    private function getPreviousApprovals()
    {
        return $this->reservation->signatories()
                    ->where('role', '!=', 'director')
                    ->where('status', 'approved')
                    ->get()
                    ->map(function ($signatory) {
                        return [
                            'name' => $signatory->user->name,
                            'role' => $signatory->role,
                            'approved_at' => $signatory->approved_at
                        ];
                    });
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Director Approval Request',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.director-approval-request',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
