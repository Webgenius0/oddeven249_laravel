<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {

    Route::controller(EventController::class)->group(function () {
        Route::post('/events', 'store');
    });
});
