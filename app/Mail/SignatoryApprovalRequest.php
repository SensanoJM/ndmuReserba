<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignatoryApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $signatory;
    public $approvalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, Signatory $signatory)
    {
        $this->reservation = $reservation;
        $this->signatory = $signatory;
        $this->approvalUrl = $signatory->approval_url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.signatory-approval-request')
                    ->subject('Approval Request for Reservation');

        // return $this->view('emails.signatory-approval-request', ['reservation' => $this->reservation])
        // ->with([
        //     'reservation' => $this->reservation,
        //     'signatory' => $this->signatory,
        //     'approvalUrl' => $this->approvalUrl, // Ensure this is passed to the view
        // ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Signatory Approval Request',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.signatory-approval-request',
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
