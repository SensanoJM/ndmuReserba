<?php

namespace App\Http\Controllers;

use App\Mail\SignatoryApprovalRequest;
use App\Models\Reservation;
use App\Models\Signatory;
use App\Models\User;
use App\Notifications\DirectorApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SignatoryApprovalController extends Controller
{
    /**
     * Check if all signatories have approved the reservation, and if so,
     * notify the School Director.
     *
     * @param  \App\Models\Reservation  $reservation
     * @return void
     */
    public function checkAndNotifyDirector($reservation)
    {
        if ($reservation->signatories()->where('status', '!=', 'approved')->doesntExist()) {
            $directors = User::where('role', 'signatory')
                ->where('position', 'school_director')
                ->get();

            if ($directors->isEmpty()) {
                // Handle the case where no director is found
                // Log the error, or send a fallback notification
                return;
            }

            foreach ($directors as $director) {
                $director->notify(new DirectorApprovalRequest($reservation));
            }
        }
    }

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
    
        $signatory->update(['status' => 'approved', 'approval_date' => now()]);
    
        $reservation = $signatory->reservation;
        if ($reservation->signatories()->where('status', '!=', 'approved')->doesntExist()) {
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
    
        $signatory->update(['status' => 'denied', 'approval_date' => now()]);
        $signatory->reservation->update(['status' => 'denied']);
    
        return redirect()->route('approval.success')->with('message', 'Reservation denied successfully.');
    }

    /**
     * Show a success page to the user after they have approved a reservation.
     *
     * @return \Illuminate\Http\Response
     */
    public function showSuccessPage()
    {
        return view('approval.success');
    }

    /**
     * Initiates the approval process for a given reservation by sending an email
     * to each signatory with a link to approve or deny the reservation.
     *
     * @param Reservation $reservation
     * @return string
     */
    public function initiateApprovalProcess(Reservation $reservation)
    {
        Log::info('Initiating approval process for reservation:', ['reservation_id' => $reservation->id]);
    
        foreach ($reservation->signatories as $signatory) {
            Log::info('Processing signatory:', ['signatory_id' => $signatory->id]);
    
            $email = $signatory->email ?? ($signatory->user->email ?? null);
    
            if (!$email) {
                Log::error('No email found for signatory:', ['signatory_id' => $signatory->id]);
                continue;
            }
    
            try {
                Mail::to($email)->send(new SignatoryApprovalRequest($reservation, $signatory));
                Log::info('Email sent successfully to:', ['email' => $email]);
            } catch (\Exception $e) {
                Log::error("Failed to send approval email to signatory:", [
                    'signatory_id' => $signatory->id,
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }
    
        return "Approval process initiated for reservation {$reservation->id}";
    }
}
