<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RenewalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Website Routes (Milestone 15)
Route::get('/', [WebsiteController::class, 'index'])->name('home');
Route::get('/about', [WebsiteController::class, 'about'])->name('about');
Route::get('/packages', [WebsiteController::class, 'packages'])->name('packages');
Route::get('/coverage', [WebsiteController::class, 'coverage'])->name('coverage');
Route::get('/faq', [WebsiteController::class, 'faq'])->name('faq');
Route::get('/notices', [WebsiteController::class, 'notices'])->name('notices');
Route::get('/contact', [WebsiteController::class, 'contact'])->name('contact');
Route::post('/apply', [WebsiteController::class, 'apply'])->name('apply');

// Authenticated Gateway redirect
Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    if ($user->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->hasRole('staff')) {
        return redirect()->route('staff.dashboard');
    }
    return redirect()->route('account.dashboard');
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

    // Audit & Accountability Routes
    Route::get('audit/logs', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('audit/staff-report', [AuditLogController::class, 'staffReport'])->name('audit.staff-report');

    // Complaint Management Routes (Admin view & assign)
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('complaints/{complaint}/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');

    // Accounting & Payroll Routes
    Route::get('accounting/accounts', [AccountingController::class, 'accounts'])->name('accounting.accounts');
    Route::post('accounting/accounts/transfer', [AccountingController::class, 'transfer'])->name('accounting.transfer');
    Route::get('accounting/expenses', [AccountingController::class, 'expenses'])->name('accounting.expenses');
    Route::post('accounting/expenses', [AccountingController::class, 'storeExpense'])->name('accounting.expenses.store');
    Route::get('accounting/payroll', [AccountingController::class, 'payroll'])->name('accounting.payroll');
    Route::post('accounting/payroll/payout', [AccountingController::class, 'storeSalary'])->name('accounting.payroll.payout.store');
    Route::post('accounting/payroll/periods', [AccountingController::class, 'storePeriod'])->name('accounting.payroll.period.store');

    // Business Reporting & Analytics Routes (Milestone 10)
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/collections', [ReportController::class, 'collections'])->name('reports.collections');
    Route::get('reports/dues', [ReportController::class, 'dues'])->name('reports.dues');
    Route::get('reports/renewals', [ReportController::class, 'renewals'])->name('reports.renewals');
    Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
    Route::get('reports/cash-flow', [ReportController::class, 'cashFlow'])->name('reports.cash-flow');

    // SMS Engine Routes (Milestone 11)
    Route::get('sms', [SmsController::class, 'index'])->name('sms.index');
    Route::post('sms/send', [SmsController::class, 'sendManual'])->name('sms.send');
    Route::get('sms/templates', [SmsController::class, 'templates'])->name('sms.templates');
    Route::patch('sms/templates/{template}', [SmsController::class, 'updateTemplate'])->name('sms.templates.update');
    Route::get('sms/gateways', [SmsController::class, 'gateways'])->name('sms.gateways');
    Route::post('sms/gateways', [SmsController::class, 'storeGateway'])->name('sms.gateways.store');
    Route::post('sms/gateways/{gateway}/activate', [SmsController::class, 'activateGateway'])->name('sms.gateways.activate');
    Route::post('sms/{log}/retry', [SmsController::class, 'retry'])->name('sms.retry');
});

// Shared Collection, Renewal & Complaint Actions for Admin & Staff
Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::post('customers/{customer}/pay', [BillingController::class, 'storePayment'])->name('customers.pay');
    Route::post('customers/{customer}/connections/{connection}/renew', [RenewalController::class, 'store'])->name('customers.renew');
    Route::post('customers/{customer}/complaints', [ComplaintController::class, 'store'])->name('customers.complaints.store');
    Route::post('complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('complaints.status');
    Route::post('complaints/{complaint}/comments', [ComplaintController::class, 'addComment'])->name('complaints.comment');
});

// Staff Domain (/staff)
Route::middleware(['auth', 'role:admin,staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/search', [StaffController::class, 'search'])->name('api.search');
    Route::get('/customers/{customer}', [StaffController::class, 'customerDetails'])->name('customer-details');
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');

    // Milestone 13: Offline PWA Sync Endpoints
    Route::get('/api/sync/bootstrap', [OfflineSyncController::class, 'bootstrapCache'])->name('sync.bootstrap');
    Route::post('/api/sync/mutations', [OfflineSyncController::class, 'processMutations'])->name('sync.mutations');
});

// Customer Portal Domain (/account) — Milestone 14
Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/invoices', [CustomerPortalController::class, 'invoices'])->name('invoices');
    Route::get('/payments', [CustomerPortalController::class, 'payments'])->name('payments');
    Route::get('/renewal', [CustomerPortalController::class, 'renewal'])->name('renewal');
    Route::post('/renewal', [CustomerPortalController::class, 'storeRenewal'])->name('renewal.store');
    Route::get('/complaints', [CustomerPortalController::class, 'complaints'])->name('complaints');
    Route::post('/complaints', [CustomerPortalController::class, 'storeComplaint'])->name('complaints.store');
    Route::post('/complaints/{complaint}/comments', [CustomerPortalController::class, 'commentComplaint'])->name('complaints.comment');
    Route::get('/profile', [CustomerPortalController::class, 'profile'])->name('profile');
});

// Shared Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
