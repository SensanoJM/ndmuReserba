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
                        'formattedEquipment' => $this->formatEquipment()
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
        $booking = $this->reservation->booking;
        if (!$booking || !$booking->equipment) {
            return 'No equipment requested';
        }
    
        // Group by equipment name and sum quantities
        $groupedEquipment = $booking->equipment
            ->groupBy('name')
            ->map(function ($group) {
                $totalQuantity = $group->sum('pivot.quantity');
                $name = ucwords(str_replace('_', ' ', $group->first()->name));
                return "{$name}: {$totalQuantity} " . ($totalQuantity > 1 ? 'pieces' : 'piece');
            });
    
        return $groupedEquipment->join(' • ');
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
