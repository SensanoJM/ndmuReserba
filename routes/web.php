<?php

use App\Http\Controllers\SignatoryApprovalController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Mail\Mailable;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/signatory-approval/{signatory}', [SignatoryApprovalController::class, 'approve'])->name('signatory.approval');

Route::get('/approval', [SignatoryApprovalController::class, 'initiateApprovalProcess']);

Route::get('/approval/success', [SignatoryApprovalController::class, 'showSuccessPage'])->name('approval.success');
