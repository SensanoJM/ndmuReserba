<?php

namespace Tests\Feature;

use App\Jobs\SendSignatoryEmailsJob;
use App\Mail\SignatoryApprovalRequest;
use App\Models\Reservation;
use App\Models\Signatory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignatoryApprovalControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_reservation()
    {
        // Create a test reservation and signatory
        $reservation = Reservation::factory()->create();
        $signatory = Signatory::factory()->create(['reservation_id' => $reservation->id]);

        // Dispatch the SendSignatoryEmailsJob job to send the email
        Mail::fake();
        dispatch(new SendSignatoryEmailsJob($reservation));

        // Get the approval URL from the email
        Mail::assertSent(SignatoryApprovalRequest::class, function ($mail) use ($signatory) {
            return $mail->hasTo($signatory->email);
        });

        Mail::assertSent(SignatoryApprovalRequest::class, function ($mail) use ($signatory, &$approvalUrl) {
            $approvalUrl = $mail->approvalUrl; // Extract the approval URL from the Mailable
            return $mail->hasTo($signatory->email);
        });

        // Simulate the signatory approving the reservation
        $response = $this->get($approvalUrl);

        // Verify that the reservation status has been updated correctly
        $this->assertEquals('approved', $signatory->fresh()->status);
    }
}
