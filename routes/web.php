<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\PackageController as AdminPackageController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\RentalController as AdminRentalController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ScannerController as AdminScannerController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\WishlistController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Public Core Pages
|--------------------------------------------------------------------------
*/

Route::get('/catalog', [ProductController::class, 'index'])->name('catalog.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
Route::get('/packages/{slug}', [PackageController::class, 'show'])->name('packages.show');
Route::get('/availability', [AvailabilityController::class, 'index'])->name('availability.index');
Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');

/*
|--------------------------------------------------------------------------
| Public Static Pages
|--------------------------------------------------------------------------
*/

Route::get('/how-to-rent', function () {
    return Inertia::render('HowToRent/Index');
})->name('how-to-rent.index');

Route::get('/contact', function () {
    return Inertia::render('Contact/Index');
})->name('contact.index');

// [UPDATE]: Menambahkan route untuk halaman Syarat & Ketentuan dan Kebijakan Privasi
Route::get('/terms', function () {
    return Inertia::render('Terms/Index');
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('Privacy/Index');
})->name('privacy');

Route::get('/booking-schedule', function () {
    return Inertia::render('BookingSchedule/Index', [
        'calendarData' => [],
    ]);
})->name('booking-schedule.index');

/*
|--------------------------------------------------------------------------
| Midtrans Webhook
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('webhooks.midtrans');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest:web')->group(function () {
    Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->name('auth.social.redirect')
        ->where('provider', 'google|github|discord');

    Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->name('auth.social.callback')
        ->where('provider', 'google|github|discord');
});

Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login', [AdminAuthenticatedSessionController::class, 'create'])
        ->name('admin.login');

    Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('admin.login.store');
});

Route::post('/admin/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:admin')
    ->name('admin.logout');

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

// [UPDATE]: Memastikan akses user diverifikasi menggunakan guard 'web'
Route::middleware(['auth:web', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('home');
    })->name('dashboard');

    /* Cart */
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    /* Checkout */
    Route::get('/checkout', function () {
        return Inertia::render('Checkout/Index');
    })->name('checkout.index');
    Route::post('/checkout', [RentalController::class, 'store'])
        ->middleware('throttle:checkout')
        ->name('checkout.store');

    /* Payment */
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/failed', [PaymentController::class, 'failed'])->name('payment.failed');
    Route::post('/payment/{code}/retry', [PaymentController::class, 'retry'])
        ->middleware('throttle:payment-retry')
        ->name('payment.retry');

    /* My Rentals */
    Route::get('/my-rentals', [RentalController::class, 'index'])->name('my-rentals.index');
    Route::get('/my-rentals/{code}', [RentalController::class, 'show'])->name('my-rentals.show');
    Route::post('/my-rentals/{code}/cancel', [RentalController::class, 'cancel'])->name('my-rentals.cancel');


    /* Wishlist */
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist', [WishlistController::class, 'store'])->name('wishlist.toggle');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');



    /* Profile */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/toggle-notification', [ProfileController::class, 'toggleNotification'])->name('profile.toggle-notification');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// [UPDATE]: Memastikan akses admin menggunakan guard 'admin' yang terpisah
Route::middleware(['auth:admin', 'role:admin', 'active'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        /* Dashboard */
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        /* Rentals */
        Route::get('/rentals', [AdminRentalController::class, 'index'])->name('rentals.index');
        Route::post('/rentals/scan', [AdminScannerController::class, 'scan'])->name('rentals.scan');
        Route::post('/rentals/{rental}/confirm-payment', [AdminRentalController::class, 'confirmPayment'])->name('rentals.confirm-payment');
        Route::post('/rentals/{rental}/cancel', [AdminRentalController::class, 'cancel'])->name('rentals.cancel');
        Route::get('/rentals/{rental}', [AdminRentalController::class, 'show'])->name('rentals.show');
        Route::post('/rentals/{rental}/pickup', [AdminRentalController::class, 'pickup'])->name('rentals.pickup');
        Route::post('/rentals/{rental}/return', [AdminRentalController::class, 'return'])->name('rentals.return');
        // [UPDATE]: Menambahkan rute activate dan dispute yang hilang.
        // Tanpa ini, tombol aksi di halaman Admin Rentals akan error karena
        // Vue memanggil route('admin.rentals.activate') dan route('admin.rentals.dispute')
        // yang sebelumnya tidak terdaftar di web.php.
        Route::post('/rentals/{rental}/activate', [AdminRentalController::class, 'activate'])->name('rentals.activate');
        Route::post('/rentals/{rental}/dispute', [AdminRentalController::class, 'dispute'])->name('rentals.dispute');
        Route::post('/rentals/{rental}/guarantee', [AdminRentalController::class, 'saveGuarantee'])->name('rentals.save-guarantee');
        Route::post('/rentals/{rental}/verify-guarantee', [AdminRentalController::class, 'verifyGuarantee'])->name('rentals.verify-guarantee');

        // [UPDATE]: Menambahkan Rute untuk fitur POS Kasir Offline
        Route::get('/pos', [\App\Http\Controllers\Admin\AdminPOSController::class, 'index'])->name('pos.index');
        Route::get('/pos/products', [\App\Http\Controllers\Admin\AdminPOSController::class, 'products'])->name('pos.products');
        Route::post('/pos/store', [\App\Http\Controllers\Admin\AdminPOSController::class, 'store'])->name('pos.store');

        /* Master Data CRUD */
        Route::resource('products', AdminProductController::class);
        Route::resource('categories', AdminCategoryController::class)->except(['create', 'show', 'edit']);
        Route::resource('packages', AdminPackageController::class);
        Route::resource('faqs', AdminFaqController::class);

        /* Payments */
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/confirm', [AdminPaymentController::class, 'confirm'])->name('payments.confirm');

        /* Users */
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');

        /* Reports */
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    });

/*
|--------------------------------------------------------------------------
| Breeze Auth Routes
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
