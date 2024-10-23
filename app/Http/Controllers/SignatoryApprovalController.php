<?php

namespace App\Http\Controllers;

use App\Mail\SignatoryApprovalRequest;
use App\Mail\DirectorApprovalRequest;
use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Services\ReservationService;

class SignatoryApprovalController extends Controller
{

    protected $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    /**
     * Approve a reservation request.
     *
     * @param  \App\Models\Signatory  $signatory
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function approve(Signatory $signatory, $token)
    {
        if ($signatory->approval_token !== $token) {
            abort(403, 'Invalid approval token');
        }
    
        $signatory->approve();
    
        $reservation = $signatory->reservation;
        $booking = $reservation->booking;
        
        // Check if all signatories have approved and update booking status if necessary
        $this->reservationService->updateBookingStatusAfterSignatoryApproval($booking);
    
        // Check if this approval completes all non-director approvals
        if ($this->allNonDirectorSignatoriesApproved($reservation)) {
            $this->notifyDirector($reservation);
        }
    
        return redirect()->route('approval.success')->with('message', 'Reservation approved successfully.');
    }

    /**
     * Deny a reservation request.
     *
     * @param  \App\Models\Signatory  $signatory
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function deny(Signatory $signatory, $token)
    {
        if ($signatory->approval_token !== $token) {
            abort(403, 'Invalid approval token');
        }
    
        $signatory->deny();
        $signatory->reservation->update(['status' => 'denied']);
    
        return redirect()->route('approval.success')->with('message', 'Reservation denied successfully.');
    }

    /**
     * Show the success page after approving or denying a reservation.
     *
     * @return \Illuminate\Http\Response
     */
    public function showSuccessPage()
    {
        return view('approval.success');
    }

    /**
     * Initiates the approval process for a reservation.
     *
     * This method sends an email to each non-director signatory associated with the reservation,
     * requesting approval. This method is typically called after a booking has been approved.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return string The outcome of the approval process initiation.
     */
    public function initiateApprovalProcess(Reservation $reservation)
    {
        Log::info('Initiating approval process for reservation:', ['reservation_id' => $reservation->id]);
    
        foreach ($reservation->signatories()->where('role', '!=', 'school_director')->get() as $signatory) {
            $this->sendApprovalEmail($signatory);
        }
    
        return "Approval process initiated for reservation {$reservation->id}";
    }

    /**
     * Check if all non-director signatories have approved the given reservation.
     * 
     * This method checks if all the signatories with roles 'adviser', 'dean', and 'school_president'
     * have approved the reservation. If all of them have approved, it returns true.
     * If any of them has not approved, or if the signatory does not exist, it returns false.
     * 
     * @param  \App\Models\Reservation  $reservation
     * @return boolean
     */
    private function allNonDirectorSignatoriesApproved(Reservation $reservation)
    {
        $nonDirectorRoles = ['adviser', 'dean', 'school_president'];
        
        foreach ($nonDirectorRoles as $role) {
            $signatory = $reservation->signatories()->where('role', $role)->first();
            if (!$signatory || $signatory->status !== 'approved') {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Send a director approval request email to the school director if the school director
     * has not been notified yet.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    private function notifyDirector(Reservation $reservation)
    {
        $director = $reservation->signatories()->where('role', 'school_director')->first();
        if ($director && $director->status === 'pending' && !$reservation->directorNotified()) {
            $email = $director->email ?? ($director->user->email ?? null);
            if ($email) {
                Mail::to($email)->send(new DirectorApprovalRequest($reservation, $director));
                $reservation->update(['director_notified_at' => now()]); // Mark as notified
            }
        }
    }

    /**
     * Sends an approval email to the given signatory.
     *
     * This method logs processing of the signatory, extracts the email from the signatory
     * or its associated user, and sends an email to the extracted email address
     * using the SignatoryApprovalRequest mailable class. If the $isDirector parameter
     * is true, it uses the DirectorApprovalRequest mailable class instead.
     *
     * If the email is not found, it logs an error and does not send the email.
     * If sending the email fails, it logs an error with the exception message.
     *
     * @param  \App\Models\Signatory  $signatory
     * @param  boolean  $isDirector
     * @return void
     */
    private function sendApprovalEmail(Signatory $signatory, $isDirector = false)
    {
        Log::info('Processing signatory:', ['signatory_id' => $signatory->id]);

        $email = $signatory->email ?? ($signatory->user->email ?? null);

        if (!$email) {
            Log::error('No email found for signatory:', ['signatory_id' => $signatory->id]);
            return;
        }

        try {
            $mailClass = $isDirector ? DirectorApprovalRequest::class : SignatoryApprovalRequest::class;
            Mail::to($email)->send(new $mailClass($signatory->reservation, $signatory));
            Log::info('Email sent successfully to:', ['email' => $email]);
        } catch (\Exception $e) {
            Log::error("Failed to send approval email to signatory:", [
                'signatory_id' => $signatory->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
    }
}
