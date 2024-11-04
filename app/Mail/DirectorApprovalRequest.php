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
                         'previousApprovals' => $this->previousApprovals,
                         'formattedEquipment' => $this->formatEquipment()
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
            ->where('role', '!=', 'school_director')
            ->where('status', 'approved')
            ->get()
            ->map(function ($signatory) {
                // Get name from either user relationship, email, or role as fallback
                $name = $signatory->user?->name 
                    ?? $signatory->email 
                    ?? ucwords(str_replace('_', ' ', $signatory->role));

                return [
                    'name' => $name,
                    'role' => ucwords(str_replace('_', ' ', $signatory->role)),
                    'approval_date' => $signatory->approval_date,
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
            with: [
                'formattedEquipment' => $this->formatEquipment(),
                'previousApprovals' => $this->previousApprovals,
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
