<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    Route::controller(EventController::class)->group(function () {
        Route::post('/events-store', 'store');
        Route::post('/register-ticket', 'registerTicket');
        Route::get('/events', 'index');
        Route::get('/event-participants', 'getParticipants');
        Route::get('/events-show', 'show');
        Route::post('/events/invite', 'sendInvitation');
        Route::post('/events/invitation/action', 'handleInvitation');
        Route::get('/events/my-invitations', 'myInvitations');
        Route::post('/events/invitation/request-payment', 'requestPayment');
        Route::post('/events/invitation/approve-payment', 'approvePayment');
        Route::post('/events/verify-ticket', 'verifyTicketCode');
        Route::get('/events/my-sent-invitations', 'mySentInvitations');
        Route::get('/events/get-user-tickets', 'getUserTickets');
        Route::get('/events/ticket-details', 'showTicketDetails');
    });
});
