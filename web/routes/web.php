<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WeddingController as AdminWeddingController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\BankTransferController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CspReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FakeCheckoutController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\GuestGroupController;
use App\Http\Controllers\GuestImportController;
use App\Http\Controllers\GuestInvitationController;
use App\Http\Controllers\GuestInvitationLinkController;
use App\Http\Controllers\GuestReminderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvitationCalendarController;
use App\Http\Controllers\InvitationDraftController;
use App\Http\Controllers\InvitationPreviewController;
use App\Http\Controllers\InvitationPublicationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\MediaAlbumController;
use App\Http\Controllers\MediaDownloadController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\PublicInvitationController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\Security\TwoFactorController;
use App\Http\Controllers\ShowcaseController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TemplateCatalogController;
use App\Http\Controllers\TemplateDemoController;
use App\Http\Controllers\TestimonialController;
use App\Http\Controllers\WeddingController;
use App\Http\Controllers\WeddingEventController;
use App\Http\Controllers\WeddingPartnerController;
use App\Http\Controllers\WeddingPhotoController;
use App\Http\Controllers\WeddingShowcaseController;
use App\Http\Middleware\RequireTwoFactor;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Discovery: served from PHP so the host matches APP_URL in every environment.
Route::get('robots.txt', RobotsController::class)->name('robots');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

// Theme catalogue. Paginated because the design count keeps growing; the same
// page answers ?bagian=kartu with just the card fragment for the load-more link.
Route::get('tema', TemplateCatalogController::class)->name('templates.index');

Route::get('undangan/contoh/{template:slug}', [TemplateDemoController::class, 'show'])
    ->middleware('throttle:public-invitation')
    ->name('templates.demo');

Route::get('undangan/contoh/{template:slug}/kalender.ics', [InvitationCalendarController::class, 'demo'])
    ->middleware('throttle:public-invitation')
    ->name('templates.calendar');

// Opt-in portfolio: only weddings whose owner enabled the showcase and whose
// publication is still active are listed (docs/PRIVACY_AND_RETENTION.md).
Route::get('inspirasi', [ShowcaseController::class, 'index'])->name('showcase.index');
Route::get('inspirasi/{slug}', [ShowcaseController::class, 'show'])
    ->middleware('throttle:public-invitation')
    ->name('showcase.show');

// Contact form. The honeypot is checked in the controller and nothing is
// written to the session beyond the status flash.
Route::post('kontak', [LeadController::class, 'store'])
    ->middleware('throttle:lead-capture')
    ->name('leads.store');

Route::view('kebijakan-privasi', 'legal.privacy')->name('legal.privacy');
Route::view('syarat-ketentuan', 'legal.terms')->name('legal.terms');

// Browsers post CSP violations without a CSRF token; the payload is truncated
// and logged, never stored (docs/SECURITY.md, section 9).
Route::post('csp-reports', CspReportController::class)
    ->middleware('throttle:60,1')
    ->name('security.csp-report');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Second factor. The password step leaves no session behind, so the guest
    // middleware is safe here (docs/SECURITY.md, section 8).
    Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('two-factor.login');
    Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email verification stays reachable before the address is confirmed so the
    // owner can resend the link (docs/SECURITY.md, section 9).
    Route::get('email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

// The customer area requires a verified address so undangan, pesanan, and guest
// links are always tied to a reachable inbox.
Route::middleware(['auth', 'verified'])->group(function (): void {
    // Security settings stay reachable for a signed-in account even while the
    // admin area waits for 2FA enrolment (RequireTwoFactor).
    Route::get('pengaturan/keamanan', [TwoFactorController::class, 'edit'])->name('security.edit');
    Route::post('pengaturan/keamanan', [TwoFactorController::class, 'confirm'])->name('security.confirm');
    Route::delete('pengaturan/keamanan', [TwoFactorController::class, 'destroy'])->name('security.destroy');

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

    // Portfolio opt-in and testimonial are owner actions, gated by the wedding
    // policy like every other edit (docs/SECURITY.md, section 3).
    Route::post('weddings/{wedding}/showcase', [WeddingShowcaseController::class, 'update'])->name('weddings.showcase.update');
    Route::post('weddings/{wedding}/testimonial', [TestimonialController::class, 'store'])->name('weddings.testimonial.store');

    // Scoped bindings: a guest or group from another wedding resolves to 404
    // instead of being authorised by UUID alone (docs/SECURITY.md, section 3).
    Route::scopeBindings()->group(function (): void {
        Route::get('weddings/{wedding}/guests', [GuestController::class, 'index'])->name('weddings.guests.index');
        Route::get('weddings/{wedding}/guests/create', [GuestController::class, 'create'])->name('weddings.guests.create');
        Route::post('weddings/{wedding}/guests', [GuestController::class, 'store'])->name('weddings.guests.store');
        Route::post('weddings/{wedding}/guests/import', [GuestImportController::class, 'store'])->name('weddings.guests.import');
        Route::get('weddings/{wedding}/guests/export', [GuestController::class, 'export'])->name('weddings.guests.export');
        Route::get('weddings/{wedding}/guests/template', [GuestImportController::class, 'template'])->name('weddings.guests.template');
        Route::get('weddings/{wedding}/guests/{guest}/edit', [GuestController::class, 'edit'])->name('weddings.guests.edit');
        Route::patch('weddings/{wedding}/guests/{guest}', [GuestController::class, 'update'])->name('weddings.guests.update');
        Route::delete('weddings/{wedding}/guests/{guest}', [GuestController::class, 'destroy'])->name('weddings.guests.destroy');

        Route::post('weddings/{wedding}/guests/{guest}/link', [GuestInvitationLinkController::class, 'store'])
            ->name('weddings.guests.link.store');
        Route::delete('weddings/{wedding}/guests/{guest}/link', [GuestInvitationLinkController::class, 'destroy'])
            ->name('weddings.guests.link.destroy');

        // Owner-triggered reminder run; the daily command reuses the action.
        Route::post('weddings/{wedding}/guests/remind', [GuestReminderController::class, 'store'])
            ->name('weddings.guests.remind');

        Route::post('weddings/{wedding}/guest-groups', [GuestGroupController::class, 'store'])
            ->name('weddings.guest-groups.store');
        Route::delete('weddings/{wedding}/guest-groups/{guestGroup}', [GuestGroupController::class, 'destroy'])
            ->name('weddings.guest-groups.destroy');

        // Gallery: photos, albums and archives are only reachable through the
        // owning wedding, so a foreign UUID resolves to 404 before authorisation.
        // Invitation photos are the couple's own pre-wedding pictures: uploaded
        // from the editor into one public album, with a chosen cover and an
        // option to show a monogram instead.
        Route::post('weddings/{wedding}/photos', [WeddingPhotoController::class, 'store'])
            ->name('weddings.photos.store');
        Route::patch('weddings/{wedding}/photos/display', [WeddingPhotoController::class, 'display'])
            ->name('weddings.photos.display');
        Route::patch('weddings/{wedding}/photos/{mediaAsset}/cover', [WeddingPhotoController::class, 'cover'])
            ->name('weddings.photos.cover');

        Route::get('weddings/{wedding}/gallery', [GalleryController::class, 'index'])->name('weddings.gallery.index');
        Route::post('weddings/{wedding}/gallery/photos', [GalleryController::class, 'store'])
            ->name('weddings.gallery.photos.store');
        Route::delete('weddings/{wedding}/gallery/photos/{mediaAsset}', [GalleryController::class, 'destroy'])
            ->name('weddings.gallery.photos.destroy');

        Route::post('weddings/{wedding}/gallery/albums', [MediaAlbumController::class, 'store'])
            ->name('weddings.gallery.albums.store');
        Route::patch('weddings/{wedding}/gallery/albums/{mediaAlbum}', [MediaAlbumController::class, 'update'])
            ->name('weddings.gallery.albums.update');
        Route::delete('weddings/{wedding}/gallery/albums/{mediaAlbum}', [MediaAlbumController::class, 'destroy'])
            ->name('weddings.gallery.albums.destroy');

        Route::post('weddings/{wedding}/gallery/archives', [GalleryController::class, 'storeArchive'])
            ->name('weddings.gallery.archives.store');
        Route::post('weddings/{wedding}/gallery/archives/{archive}/retry', [GalleryController::class, 'retryArchive'])
            ->name('weddings.gallery.archives.retry');
        Route::delete('weddings/{wedding}/gallery/archives/{archive}', [GalleryController::class, 'destroyArchive'])
            ->name('weddings.gallery.archives.destroy');
    });

    Route::get('weddings/{wedding}/orders/create', [OrderController::class, 'create'])->name('weddings.orders.create');
    Route::post('weddings/{wedding}/orders', [OrderController::class, 'store'])->name('weddings.orders.store');

    Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/checkout', [CheckoutController::class, 'store'])->name('orders.checkout');

    // Manual bank transfer: instructions, proof upload, and reopening a closed
    // payment window with a fresh unique code.
    Route::get('checkout/transfer/{order}', [BankTransferController::class, 'show'])->name('checkout.transfer.show');
    Route::post('orders/{order}/payment-confirmation', [BankTransferController::class, 'confirm'])->name('orders.payment.confirm');
    Route::post('orders/{order}/payment-code', [BankTransferController::class, 'regenerate'])->name('orders.payment.regenerate');

    // Sandbox-only provider screen, closed by configuration outside local/testing.
    Route::get('checkout/fake/{order}', [FakeCheckoutController::class, 'show'])->name('checkout.fake.show');
    Route::post('checkout/fake/{order}', [FakeCheckoutController::class, 'complete'])->name('checkout.fake.complete');
});

Route::middleware(['auth', 'verified', 'can:access-admin', RequireTwoFactor::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('weddings', [AdminWeddingController::class, 'index'])->name('weddings.index');
    Route::get('weddings/{wedding}', [AdminWeddingController::class, 'show'])->name('weddings.show');
    Route::post('weddings/{wedding}/suspend', [AdminWeddingController::class, 'suspend'])->name('weddings.suspend');

    Route::get('analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');

    Route::get('leads', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::post('leads/{lead}/contacted', [AdminLeadController::class, 'contacted'])->name('leads.contacted');

    Route::get('testimonials', [AdminTestimonialController::class, 'index'])->name('testimonials.index');
    Route::post('testimonials/{testimonial}/approve', [AdminTestimonialController::class, 'approve'])->name('testimonials.approve');
    Route::post('testimonials/{testimonial}/reject', [AdminTestimonialController::class, 'reject'])->name('testimonials.reject');

    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    // Registered before the {user} route so the literal path always wins.
    Route::get('users/export', [AdminUserController::class, 'export'])->name('users.export');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/activate', [AdminUserController::class, 'activate'])->name('users.activate');
    Route::post('users/{user}/crm', [AdminUserController::class, 'updateCrm'])->name('users.crm.update');

    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/verify', [AdminOrderController::class, 'verify'])->name('orders.verify');
    Route::post('orders/{order}/reject', [AdminOrderController::class, 'reject'])->name('orders.reject');
    Route::get('orders/{order}/proofs/{confirmation}', [AdminOrderController::class, 'proof'])->name('orders.proof');

    Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::post('payments/webhook/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('payments.webhook');

Route::get('u/{slug}', [PublicInvitationController::class, 'show'])
    ->middleware('throttle:public-invitation')
    ->name('invitations.public');

// "Simpan tanggal": generated server-side from the same snapshot, so the file
// always matches the events guests are reading on the page.
Route::get('u/{slug}/kalender.ics', [InvitationCalendarController::class, 'show'])
    ->middleware('throttle:public-invitation')
    ->name('invitations.calendar');

// Public gallery: only albums marked public, behind the same publication gate.
Route::get('u/{slug}/galeri', [PublicGalleryController::class, 'show'])
    ->middleware('throttle:public-gallery')
    ->name('invitations.gallery');

// Media bytes are only served through short-lived signed URLs; the disk itself
// is private and not linked from the web root (docs/MEDIA_STORAGE.md, section 5).
Route::get('media/photos/{mediaAsset}/download', [MediaDownloadController::class, 'asset'])
    ->middleware('signed')
    ->name('media.photos.download');

Route::get('media/archives/{archive}/download', [MediaDownloadController::class, 'archive'])
    ->middleware('signed')
    ->name('media.archives.download');

// Personalised invitation and RSVP. Rate limited because the token is the
// only credential (docs/SECURITY.md, section 9).
Route::get('g/{token}', [GuestInvitationController::class, 'show'])
    ->middleware('throttle:guest-invitation')
    ->name('guests.invitation.show');

Route::post('g/{token}/rsvp', [GuestInvitationController::class, 'rsvp'])
    ->middleware('throttle:guest-rsvp')
    ->name('guests.invitation.rsvp');
