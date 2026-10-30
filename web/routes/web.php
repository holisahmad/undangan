<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationDraftController;
use App\Http\Controllers\InvitationPreviewController;
use App\Http\Controllers\InvitationPublicationController;
use App\Http\Controllers\PublicInvitationController;
use App\Http\Controllers\WeddingController;
use App\Http\Controllers\WeddingEventController;
use App\Http\Controllers\WeddingPartnerController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('weddings', [WeddingController::class, 'index'])->name('weddings.index');
    Route::get('weddings/create', [WeddingController::class, 'create'])->name('weddings.create');
    Route::post('weddings', [WeddingController::class, 'store'])->name('weddings.store');
    Route::get('weddings/{wedding}', [WeddingController::class, 'show'])->name('weddings.show');
    Route::get('weddings/{wedding}/edit', [WeddingController::class, 'edit'])->name('weddings.edit');
    Route::patch('weddings/{wedding}', [WeddingController::class, 'update'])->name('weddings.update');

    Route::post('weddings/{wedding}/partners', [WeddingPartnerController::class, 'store'])->name('weddings.partners.store');
    Route::patch('weddings/{wedding}/partners/{partner}', [WeddingPartnerController::class, 'update'])->name('weddings.partners.update');
    Route::delete('weddings/{wedding}/partners/{partner}', [WeddingPartnerController::class, 'destroy'])->name('weddings.partners.destroy');

    Route::post('weddings/{wedding}/events', [WeddingEventController::class, 'store'])->name('weddings.events.store');
    Route::patch('weddings/{wedding}/events/{event}', [WeddingEventController::class, 'update'])->name('weddings.events.update');
    Route::delete('weddings/{wedding}/events/{event}', [WeddingEventController::class, 'destroy'])->name('weddings.events.destroy');

    Route::put('weddings/{wedding}/draft', [InvitationDraftController::class, 'update'])->name('weddings.draft.update');

    Route::get('weddings/{wedding}/preview', [InvitationPreviewController::class, 'show'])->name('weddings.preview');

    Route::post('weddings/{wedding}/publish', [InvitationPublicationController::class, 'store'])->name('weddings.publish');
    Route::post('weddings/{wedding}/unpublish', [InvitationPublicationController::class, 'destroy'])->name('weddings.unpublish');
});

Route::get('u/{slug}', [PublicInvitationController::class, 'show'])
    ->middleware('throttle:public-invitation')
    ->name('invitations.public');
