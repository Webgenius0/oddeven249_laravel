<?php

use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Web\Backend\CategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Backend\DashBoardController;
use App\Http\Controllers\Web\Backend\DynamicPageController;
use App\Http\Controllers\Web\Backend\ProfileController;
use App\Http\Controllers\Web\Backend\SocialMediaController;
use App\Http\Controllers\Web\Backend\SystemSettingController;
use App\Http\Controllers\Web\Backend\FeedbackController;
use App\Http\Controllers\Web\Backend\SupportController;

Route::get('/dashboard', [DashBoardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::controller(SystemSettingController::class)->group(function () {
    Route::get('/system-setting', 'index')->name('system.index');
    Route::post('/system-setting', 'update')->name('system.update');
});
Route::controller(ProfileController::class)->group(function () {
    Route::post('/update-profile-picture', 'UpdateProfilePicture')->name('update.profile.picture');
    Route::post('/update-profile-password', 'UpdatePassword')->name('update.Password');
    //! Route for ProfileController
    Route::get('/profile', 'showProfile')->name('profile.setting');
    Route::post('/update-profile', 'UpdateProfile')->name('update.profile');
});
Route::controller(SocialMediaController::class)->group(function () {
    Route::get('/social-media', 'index')->name('social.index');
    Route::post('/social-media', 'update')->name('social.update');
    Route::delete('/social-media/{id}', 'destroy')->name('social.delete');
});
Route::controller(DynamicPageController::class)->group(function () {
    Route::get('/dynamic-page', 'index')->name('dynamic_page.index');
    Route::get('/dynamic-page/create', 'create')->name('dynamic_page.create');
    Route::post('/dynamic-page/store', 'store')->name('dynamic_page.store');
    Route::get('/dynamic-page/edit/{id}', 'edit')->name('dynamic_page.edit');
    Route::post('/dynamic-page/update/{id}', 'update')->name('dynamic_page.update');
    Route::get('/dynamic-page/status/{id}', 'status')->name('dynamic_page.status');
    Route::delete('/dynamic-page/destroy/{id}', 'destroy')->name('dynamic_page.destroy');
});
Route::prefix('category')->name('category.')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('index');
    Route::get('/create', [CategoryController::class, 'create'])->name('create');
    Route::post('/store', [CategoryController::class, 'store'])->name('store');
    Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit');
    Route::post('/update/{id}', [CategoryController::class, 'update'])->name('update');
    Route::get('/status/{id}', [CategoryController::class, 'status'])->name('status');
    Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('destroy');
});
Route::prefix('feedback')->name('feedback.')->group(function () {
    Route::get('/', [FeedbackController::class, 'index'])->name('index');
    Route::get('/status/{id}', [FeedbackController::class, 'status'])->name('status');
    Route::delete('/destroy/{id}', [FeedbackController::class, 'destroy'])->name('destroy');
    Route::get('/show/{id}', [FeedbackController::class, 'show'])->name('show');
});
Route::prefix('support')->name('support.')->group(function () {
    Route::get('/', [SupportController::class, 'index'])->name('index');
    Route::get('/show/{id}', [SupportController::class, 'show'])->name('show');
    Route::post('/reply/{id}', [SupportController::class, 'adminReply'])->name('reply');
    Route::delete('/destroy/{id}', [SupportController::class, 'destroy'])->name('destroy');
});
