<?php

namespace App\Http\Controllers;

use App\Mail\SignatoryApprovalRequest;
use App\Mail\DirectorApprovalRequest;
use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SignatoryApprovalController extends Controller
{

    /**
     * Approve a reservation request.
     *
     * @param  \App\Models\Reservation  $reservation
     * @param  int  $signatoryId
     * @return string
     */
    public function approve(Signatory $signatory, $token)
    {
        if ($signatory->approval_token !== $token) {
            abort(403, 'Invalid approval token');
        }
    
        $signatory->approve();
    
        $reservation = $signatory->reservation;
        if ($reservation->allNonDirectorSignatoriesApproved()) {
            $this->notifyDirector($reservation);
        } elseif ($signatory->role === 'director' && $reservation->signatories()->where('status', '!=', 'approved')->doesntExist()) {
            $reservation->update(['status' => 'approved']);
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
    
        foreach ($reservation->signatories()->where('role', '!=', 'director')->get() as $signatory) {
            $this->sendApprovalEmail($signatory);
        }
    
        return "Approval process initiated for reservation {$reservation->id}";
    }

    /**
     * Notify the School Director of a reservation that is ready for final approval.
     *
     * This method is called when all non-director signatories have approved a reservation.
     * It sends an email to the School Director signatory, requesting final approval.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    private function notifyDirector(Reservation $reservation)
    {
        $director = $reservation->signatories()->where('role', 'director')->first();
        if ($director) {
            $this->sendApprovalEmail($director, true);
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
