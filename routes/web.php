<?php

use App\Http\Controllers\SignatoryApprovalController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Mail\SignatoryApprovalRequest;

Route::get('/', function () {
    return view('welcome');
});

//Routes for Emails, SignatoryApprovalController; to test http://localhost/approval/1 (assuming 1 is a valid reservation ID)
Route::get('/signatory-approval/{signatory}/{token}', [SignatoryApprovalController::class, 'approve'])->name('signatory.approval');
Route::get('/signatory-denial/{signatory}/{token}', [SignatoryApprovalController::class, 'deny'])->name('signatory.denial');

Route::get('/approval/success', [SignatoryApprovalController::class, 'showSuccessPage'])->name('approval.success');
Route::get('/approval', [SignatoryApprovalController::class, 'initiateApprovalProcess']);
Route::get('/approval/{reservation}', [SignatoryApprovalController::class, 'initiateApprovalProcess']);

//Routes for Testing
Route::get('/test-email', function () {
    $testEmail = 'sensanomarlu@gmail.com';
    $reservation = \App\Models\Reservation::first(); // Get the first reservation for testing
    $signatory = $reservation->signatories->first(); // Get the first signatory for testing

    Mail::to($testEmail)->send(new SignatoryApprovalRequest($reservation, $signatory));

    return "Test email sent to $testEmail";
});