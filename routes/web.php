<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RenewalController;
use App\Http\Controllers\StaffController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Root
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Authenticated Gateway redirect
Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->hasRole('staff')) {
        return redirect()->route('staff.dashboard');
    }
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Admin Domain (/admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Customer CRM Routes
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/connections/{connection}/package', [CustomerController::class, 'changePackage'])
        ->name('customers.change-package');
    Route::post('pppoe/{credential}/reveal-password', [CustomerController::class, 'revealPppoePassword'])
        ->name('pppoe.reveal-password');

    // Billing & Payment Routes
    Route::get('billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
    Route::get('billing/payments', [BillingController::class, 'payments'])->name('billing.payments');
    Route::post('billing/payments/{payment}/reverse', [BillingController::class, 'reverse'])->name('billing.payments.reverse');
    Route::get('billing/receipts/{payment}', [BillingController::class, 'receipt'])->name('billing.receipt');

    // Renewal Routes
    Route::get('billing/renewals', [RenewalController::class, 'index'])->name('billing.renewals');
});

// Shared Collection & Renewal Actions for Admin & Staff
Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::post('customers/{customer}/pay', [BillingController::class, 'storePayment'])->name('customers.pay');
    Route::post('customers/{customer}/connections/{connection}/renew', [RenewalController::class, 'store'])->name('customers.renew');
});

// Staff Domain (/staff)
Route::middleware(['auth', 'role:admin,staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/search', [StaffController::class, 'search'])->name('api.search');
    Route::get('/customers/{customer}', [StaffController::class, 'customerDetails'])->name('customer-details');
});

// Shared Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
