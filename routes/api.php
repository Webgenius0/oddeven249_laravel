<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\ContestController;
use App\Http\Controllers\Api\DealsController;
use App\Http\Controllers\Api\InteractionController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\SocialMediaController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\User\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/social-login', [SocialAuthController::class, 'socialLogin']);

Route::controller(RegisterController::class)->prefix('users/register')->group(function () {
    // User Register
    Route::post('/', 'userRegister');

    // Verify OTP
    Route::post('/otp-verify', 'otpVerify');

    // Resend OTP
    Route::post('/otp-resend', 'otpResend');
    //email exists check
    Route::post('/email-exists', 'emailExists');

    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/verify-forgot-password-otp', 'verifyForgotPasswordOtp');
    Route::post('/reset-password', 'resetPassword');
    Route::post('/resend-forgot-password-otp', 'resendForgotPasswordOtp');
});
Route::controller(LoginController::class)->prefix('users/login')->group(function () {

    // User Login
    Route::post('/', 'userLogin');

    // Verify Email
    Route::post('/email-verify', 'emailVerify');

    // Resend OTP
    Route::post('/otp-resend', 'otpResend');

    // Verify OTP
    Route::post('/otp-verify', 'otpVerify');

    //Reset Password
    Route::post('/reset-password', 'resetPassword');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(SystemSettingController::class)->group(function () {
        Route::get('/site-settings', 'index');
    });

    Route::controller(SocialMediaController::class)->group(function () {
        Route::get('/social-links', 'index');
    });

    Route::prefix('user')->controller(UserController::class)->group(function () {
        Route::get('/', 'userDetails');
        Route::post('/update', 'updateUser');
        Route::post('/update-password', 'updatePassword');
        Route::delete('/delete-account', 'deleteAccount');
        Route::post('/logout', 'logoutUser');
    });
    Route::prefix('deals')->controller(DealsController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/store', 'store');
        Route::post('/update-status', 'updateStatus');
        Route::get('/show', 'show');
        Route::post('/rate-deal', 'rateDeal');
        Route::get('/rating-details', 'getRatingDetails');
        Route::post('/submit-delivery', 'submitDelivery');
        Route::post('/request-extension', 'requestExtension');
        Route::post('/process-delivery', 'processDeliveryAction');
        Route::post('/process-extension', 'processExtensionAction');
        Route::get('/extension-history', 'getAllExtensionRequests');
    });

    Route::prefix('portfolios')->controller(PortfolioController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/show', 'show');
        Route::post('/update', 'update');
        Route::get('/my-portfolio', 'myPortfolios');
        Route::post('/store', 'store');
        Route::post('/toggle-bookmark', 'toggleBookmark');
        Route::get('/my-bookmarks', 'myBookmarks');
    });

    Route::prefix('portfolios')->controller(PortfolioController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/show', 'show');
        Route::post('/update', 'update');
        Route::get('/my-portfolio', 'myPortfolios');
        Route::post('/store', 'store');
        Route::post('/toggle-bookmark', 'toggleBookmark');
        Route::get('/my-bookmarks', 'myBookmarks');
    });

    Route::prefix('portfolios')->controller(PortfolioController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/show', 'show');
        Route::post('/update', 'update');
        Route::get('/my-portfolio', 'myPortfolios');
        Route::post('/store', 'store');
        Route::post('/toggle-bookmark', 'toggleBookmark');
        Route::get('/my-bookmarks', 'myBookmarks');
    });
    Route::prefix('contest')->controller(ContestController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/show', 'show');
        Route::get('/contest-details', 'contestDetails');
        Route::post('/update', 'update');
        Route::get('/my-contests', 'myContests');
        Route::get('/participated-contests', 'participatedContests');
        Route::post('/store', 'store');
        Route::post('/join-contest', 'join');
        Route::get('/participants', 'allParticipants');
    });

    Route::prefix('interactions')->controller(InteractionController::class)->group(function () {
        Route::post('/store', 'handle');
    });
});
