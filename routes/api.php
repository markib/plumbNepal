<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BookingProposalController;
use App\Http\Controllers\Api\ServiceTypeController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AiController;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::get('service-types', [ServiceTypeController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'createBooking']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::get('dispatch/search', [DispatchController::class, 'search']);
        Route::get('bookings/{booking}/nearby-plumbers', [DispatchController::class, 'searchBooking']);
        Route::post('dispatch/availability', [DispatchController::class, 'updateAvailability']);

        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus']);
        Route::get('bookings/{booking}/track', [BookingController::class, 'track']);
        Route::post('bookings/{booking}/invite-plumber', [BookingController::class, 'invitePlumber']);

        Route::get('plumber/open-requests', [BookingProposalController::class, 'openRequests']);
        Route::get('plumber/assigned-jobs', [BookingProposalController::class, 'assignedJobs']);
        Route::post('bookings/{booking}/proposals', [BookingProposalController::class, 'store']);
        Route::get('customer/proposals', [BookingProposalController::class, 'customerProposals']);
        Route::get('customer/job-orders', [BookingProposalController::class, 'customerJobOrders']);
        Route::post('bookings/{booking}/proposals/{proposal}/accept', [BookingProposalController::class, 'accept']);
        Route::post('bookings/{booking}/start-job', [BookingProposalController::class, 'startJob']);
        Route::post('bookings/{booking}/complete-job', [BookingProposalController::class, 'completeJob']);

        Route::post('verification/upload', [VerificationController::class, 'uploadDocument']);
        Route::post('verification/submit', [VerificationController::class, 'submitForReview']);
        Route::get('verification/status', [VerificationController::class, 'status']);

        Route::post('payments/initiate', [PaymentController::class, 'initiate']);
        Route::post('payments/callback', [PaymentController::class, 'callback']);
    });

    Route::post('/ai/diagnose', [AiController::class, 'diagnose']);
});

