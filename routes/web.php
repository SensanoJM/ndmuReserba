<?php

use App\Http\Controllers\SignatoryApprovalController;
use App\Mail\SignatoryApprovalRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

//Routes for Emails, SignatoryApprovalController; to test http://localhost/approval/1 (assuming 1 is a valid reservation ID)
Route::get('/approval/success', [SignatoryApprovalController::class, 'showSuccessPage'])->name('approval.success');

Route::prefix('signatory')->group(function () {
    Route::get('/approve/{signatory}/{token}', [SignatoryApprovalController::class, 'approve'])->name('signatory.approval');
    Route::get('/deny/{signatory}/{token}', [SignatoryApprovalController::class, 'deny'])->name('signatory.denial');
});

// If you need to manually initiate the approval process (e.g., for testing)
Route::post('/reservation/{reservation}/initiate-approval', [SignatoryApprovalController::class, 'initiateApprovalProcess'])
    ->name('reservation.initiate-approval')
    ->middleware('auth'); // Ensure only authenticated users can access this route
