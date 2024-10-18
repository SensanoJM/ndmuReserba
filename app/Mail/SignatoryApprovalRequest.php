<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Illuminate\Mail\Mailables\Attachment;

class SignatoryApprovalRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $signatory;
    public $approvalUrl;
    public $denialUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Reservation $reservation, Signatory $signatory)
    {
        $this->reservation = $reservation;
        $this->signatory = $signatory;
    
        // Ensure the signatory has an approval token
        if (!$this->signatory->approval_token) {
            $this->signatory->approval_token = Str::random(32);
            $this->signatory->save();
        }
    
        $this->approvalUrl = $signatory->approval_url;
        $this->denialUrl = $signatory->deny_url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.signatory-approval-request')
                    ->subject('Approval Request for Reservation')
                    ->with([
                        'approvalUrl' => $this->approvalUrl,
                        'denialUrl' => $this->denialUrl,
                        'attachments' => $this->attachments,
                    ]);
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
            with: [
                'formattedEquipment' => $this->formatEquipment(),
            ]
        );
    }

    protected function formatEquipment(): string
{
    $equipment = $this->reservation->booking->equipment;
    if (empty($equipment)) {
        return 'No equipment requested';
    }

    if (is_string($equipment)) {
        return $equipment;
    }

    if (is_array($equipment)) {
        return collect($equipment)->map(function ($quantity, $name) {
            return "$name: $quantity";
        })->join(', ');
    }

    return 'Equipment data format is invalid';
}

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return $this->reservation->booking->attachments->map(function ($attachment) {
            return Attachment::fromStorage($attachment->file_path)
                             ->as($attachment->file_name)
                             ->withMime($attachment->file_type);
        })->toArray();
    }
}
