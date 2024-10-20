<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Reservation;
use App\Models\Signatory;
use App\Models\User;
use Illuminate\Support\Str;
use App\Jobs\SendSignatoryEmailsJob;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function approveBooking(Booking $booking)
    {
        return DB::transaction(function () use ($booking) {
            if ($booking->status === 'pending') {
                return $this->initialApprove($booking);
            } elseif ($booking->status === 'in_review' && $this->allSignatoriesApproved($booking)) {
                return $this->finalApprove($booking);
            }
            return false;
        });
    }

    public function denyBooking(Booking $booking)
    {
        return DB::transaction(function () use ($booking) {
            $booking->status = 'denied';
            $booking->save();
            if ($booking->reservation) {
                $booking->reservation->update(['status' => 'denied']);
            }
            return true;
        });
    }

    public function allSignatoriesApproved(Booking $booking): bool
    {
        return $booking->reservation->signatories()->where('status', '!=', 'approved')->doesntExist();
    }

    private function initialApprove(Booking $booking)
    {
        $booking->status = 'in_review';
        $booking->save();

        $reservation = $booking->reservation()->create([
            'status' => 'pending',
            'admin_approval_date' => now(),
        ]);

        $this->createSignatories($reservation);

        SendSignatoryEmailsJob::dispatch($reservation);

        return true;
    }

    private function finalApprove(Booking $booking)
    {
        $booking->update(['status' => 'approved']);
        $booking->reservation->update([
            'status' => 'approved',
            'final_approval_date' => now()
        ]);

        return true;
    }

    private function createSignatories(?Reservation $reservation)
    {
        if (!$reservation) {
            return;
        }

        $booking = $reservation->booking;
        $signatoryRoles = [
            'adviser' => $booking->approvers->where('role', 'adviser')->first()->email,
            'dean' => $booking->approvers->where('role', 'dean')->first()->email,
            'school_president' => $this->getSchoolPresidentEmail(),
            'school_director' => $this->getSchoolDirectorEmail(),
        ];

        $signatories = [];
        foreach ($signatoryRoles as $role => $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $signatories[] = [
                    'reservation_id' => $reservation->id,
                    'role' => $role,
                    'email' => $email,
                    'user_id' => User::where('email', $email)->value('id'),
                    'status' => 'pending',
                    'approval_token' => Str::random(32),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Signatory::insert($signatories);
    }

    private function getSchoolPresidentEmail()
    {
        return User::where('role', 'signatory')
            ->where('position', 'school_president')
            ->value('email');
    }

    private function getSchoolDirectorEmail()
    {
        return User::where('role', 'signatory')
            ->where('position', 'school_director')
            ->value('email');
    }
}