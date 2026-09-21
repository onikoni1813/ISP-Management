<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\OfflineSyncController;
use App\Http\Controllers\PackageAreaController;
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
Route::get('/p/{slug}', [WebsiteController::class, 'customPage'])->name('page.custom');
Route::post('/apply', [WebsiteController::class, 'apply'])->middleware('throttle:5,1')->name('apply');

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
})->middleware(['auth'])->name('dashboard');

// Admin Domain (/admin)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        $today = \Carbon\Carbon::today();

        $totalCustomers = \App\Models\Customer::count();
        $activeCustomers = \App\Models\Customer::where('status', 'active')->count();
        $inactiveCustomers = $totalCustomers - $activeCustomers;

        $todayCollection = (float) \App\Models\Payment::whereDate('paid_at', $today)
            ->where('status', 'completed')
            ->sum('amount');
        $todayCollectionCount = \App\Models\Payment::whereDate('paid_at', $today)
            ->where('status', 'completed')
            ->count();

        $totalDue = (float) \App\Models\Invoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->sum('due_amount');
        $dueCustomersCount = \App\Models\Customer::where('balance', '<', 0)->count();

        $openTickets = \App\Models\Complaint::whereIn('status', ['open', 'assigned', 'in_progress'])->count();
        $urgentTickets = \App\Models\Complaint::whereIn('status', ['open', 'assigned', 'in_progress'])
            ->where('priority', 'urgent')
            ->count();

        // Pending Staff Collections awaiting Admin Approval
        $pendingApprovalsCount = \App\Models\Payment::where('status', 'pending')->count();
        $pendingApprovalsAmount = (float) \App\Models\Payment::where('status', 'pending')->sum('amount');
        $pendingPayments = \App\Models\Payment::with(['customer', 'collector'])
            ->where('status', 'pending')
            ->latest('id')
            ->take(5)
            ->get();

        $recentPayments = \App\Models\Payment::with('customer')
            ->where('status', 'completed')
            ->latest('paid_at')
            ->take(5)
            ->get();

        $recentComplaints = \App\Models\Complaint::with(['customer', 'assignee'])
            ->latest('id')
            ->take(5)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'inactive_customers' => $inactiveCustomers,
                'today_collection' => $todayCollection,
                'today_collection_count' => $todayCollectionCount,
                'total_due' => $totalDue,
                'due_customers_count' => $dueCustomersCount,
                'open_tickets' => $openTickets,
                'urgent_tickets' => $urgentTickets,
                'pending_approvals_count' => $pendingApprovalsCount,
                'pending_approvals_amount' => $pendingApprovalsAmount,
            ],
            'pendingPayments' => $pendingPayments,
            'recentPayments' => $recentPayments,
            'recentComplaints' => $recentComplaints,
        ]);
    })->name('dashboard');

    // Customer CRM Routes
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/connections/{connection}/package', [CustomerController::class, 'changePackage'])
        ->name('customers.change-package');
    Route::post('pppoe/{credential}/reveal-password', [CustomerController::class, 'revealPppoePassword'])
        ->name('pppoe.reveal-password');
    Route::post('customers/{customer}/send-credentials-sms', [CustomerController::class, 'sendCredentialsSms'])
        ->name('customers.send-credentials-sms');

    // Packages & Coverage Areas Management
    Route::get('packages', [PackageAreaController::class, 'index'])->name('packages.index');
    Route::post('packages', [PackageAreaController::class, 'storePackage'])->name('packages.store');
    Route::patch('packages/{package}', [PackageAreaController::class, 'updatePackage'])->name('packages.update');
    Route::delete('packages/{package}', [PackageAreaController::class, 'destroyPackage'])->name('packages.destroy');

    Route::post('areas', [PackageAreaController::class, 'storeArea'])->name('areas.store');
    Route::patch('areas/{area}', [PackageAreaController::class, 'updateArea'])->name('areas.update');
    Route::delete('areas/{area}', [PackageAreaController::class, 'destroyArea'])->name('areas.destroy');

    // Billing & Payment Routes
    Route::get('billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
    Route::get('billing/invoices/{invoice}', [BillingController::class, 'showInvoice'])->name('billing.invoices.show');
    Route::get('billing/payments', [BillingController::class, 'payments'])->name('billing.payments');
    Route::post('billing/payments/{payment}/approve', [BillingController::class, 'approve'])->name('billing.payments.approve');
    Route::post('billing/payments/{payment}/reverse', [BillingController::class, 'reverse'])->name('billing.payments.reverse');
    Route::get('billing/receipts/{payment}', [BillingController::class, 'receipt'])->name('billing.receipt');

    // Renewal Routes
    Route::get('billing/renewals', [RenewalController::class, 'index'])->name('billing.renewals');

    // Staff Management Routes (Admin exclusive)
    Route::resource('staff-members', \App\Http\Controllers\Admin\StaffManagementController::class);
    Route::post('staff-members/{user}/toggle-status', [\App\Http\Controllers\Admin\StaffManagementController::class, 'toggleStatus'])
        ->name('staff-members.toggle-status');

    // Audit & Accountability Routes
    Route::get('audit/logs', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('audit/staff-report', [AuditLogController::class, 'staffReport'])->name('audit.staff-report');

    // Complaint Management Routes (Admin view & assign)
    Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('complaints/{complaint}/assign', [ComplaintController::class, 'assign'])->name('complaints.assign');

    // Accounting & Payroll Routes
    Route::get('accounting/accounts', [AccountingController::class, 'accounts'])->name('accounting.accounts');
    Route::post('accounting/accounts', [AccountingController::class, 'storeAccount'])->name('accounting.accounts.store');
    Route::post('accounting/accounts/transfer', [AccountingController::class, 'transfer'])->name('accounting.transfer');
    Route::post('accounting/transfer', [AccountingController::class, 'transfer'])->name('accounting.transfer.alias');
    Route::match(['put', 'post'], 'accounting/accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accounting.accounts.update');
    Route::patch('accounting/accounts/{account}/toggle-status', [AccountingController::class, 'toggleAccountStatus'])->name('accounting.accounts.toggle-status');
    Route::delete('accounting/accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accounting.accounts.destroy');
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
    Route::post('sms/bulk-send', [SmsController::class, 'sendBulk'])->name('sms.bulk-send');
    Route::get('sms/templates', [SmsController::class, 'templates'])->name('sms.templates');
    Route::patch('sms/templates/{template}', [SmsController::class, 'updateTemplate'])->name('sms.templates.update');
    Route::get('sms/gateways', [SmsController::class, 'gateways'])->name('sms.gateways');
    Route::post('sms/gateways', [SmsController::class, 'storeGateway'])->name('sms.gateways.store');
    Route::put('sms/gateways/{gateway}', [SmsController::class, 'updateGateway'])->name('sms.gateways.update');
    Route::delete('sms/gateways/{gateway}', [SmsController::class, 'destroyGateway'])->name('sms.gateways.destroy');
    Route::post('sms/gateways/{gateway}/activate', [SmsController::class, 'activateGateway'])->name('sms.gateways.activate');
    Route::post('sms/gateways/{gateway}/test', [SmsController::class, 'testGateway'])->name('sms.gateways.test');
    Route::get('sms/gateways/{gateway}/balance', [SmsController::class, 'checkBalance'])->name('sms.gateways.balance');
    Route::post('sms/{log}/retry', [SmsController::class, 'retry'])->name('sms.retry');

    // Website CMS Routes (Milestone 16)
    Route::get('cms', [App\Http\Controllers\CmsController::class, 'index'])->name('cms.index');
    Route::get('cms/pages', [App\Http\Controllers\CmsController::class, 'pages'])->name('cms.pages');
    Route::post('cms/pages', [App\Http\Controllers\CmsController::class, 'storePage'])->name('cms.pages.store');
    Route::patch('cms/pages/{page}', [App\Http\Controllers\CmsController::class, 'updatePage'])->name('cms.pages.update');
    Route::post('cms/pages/{page}/toggle', [App\Http\Controllers\CmsController::class, 'togglePagePublish'])->name('cms.pages.toggle');
    Route::delete('cms/pages/{page}', [App\Http\Controllers\CmsController::class, 'destroyPage'])->name('cms.pages.destroy');

    Route::get('cms/banners', [App\Http\Controllers\CmsController::class, 'banners'])->name('cms.banners');
    Route::post('cms/banners', [App\Http\Controllers\CmsController::class, 'storeBanner'])->name('cms.banners.store');
    Route::patch('cms/banners/{banner}', [App\Http\Controllers\CmsController::class, 'updateBanner'])->name('cms.banners.update');
    Route::post('cms/banners/{banner}/toggle', [App\Http\Controllers\CmsController::class, 'toggleBannerActive'])->name('cms.banners.toggle');
    Route::delete('cms/banners/{banner}', [App\Http\Controllers\CmsController::class, 'destroyBanner'])->name('cms.banners.destroy');

    Route::get('cms/faqs', [App\Http\Controllers\CmsController::class, 'faqs'])->name('cms.faqs');
    Route::post('cms/faqs', [App\Http\Controllers\CmsController::class, 'storeFaq'])->name('cms.faqs.store');
    Route::patch('cms/faqs/{faq}', [App\Http\Controllers\CmsController::class, 'updateFaq'])->name('cms.faqs.update');
    Route::post('cms/faqs/{faq}/toggle', [App\Http\Controllers\CmsController::class, 'toggleFaqPublish'])->name('cms.faqs.toggle');
    Route::delete('cms/faqs/{faq}', [App\Http\Controllers\CmsController::class, 'destroyFaq'])->name('cms.faqs.destroy');

    Route::get('cms/notices', [App\Http\Controllers\CmsController::class, 'notices'])->name('cms.notices');
    Route::post('cms/notices', [App\Http\Controllers\CmsController::class, 'storeNotice'])->name('cms.notices.store');
    Route::patch('cms/notices/{notice}', [App\Http\Controllers\CmsController::class, 'updateNotice'])->name('cms.notices.update');
    Route::post('cms/notices/{notice}/toggle', [App\Http\Controllers\CmsController::class, 'toggleNoticePublish'])->name('cms.notices.toggle');
    Route::delete('cms/notices/{notice}', [App\Http\Controllers\CmsController::class, 'destroyNotice'])->name('cms.notices.destroy');

    Route::get('cms/settings', [App\Http\Controllers\CmsController::class, 'settings'])->name('cms.settings');
    Route::post('cms/settings', [App\Http\Controllers\CmsController::class, 'updateSettings'])->name('cms.settings.update');
});

// Shared Collection, Renewal & Complaint Actions for Admin & Staff
Route::middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::post('customers/{customer}/pay', [BillingController::class, 'storePayment'])->name('customers.pay');
    Route::post('customers/{customer}/connections/{connection}/renew', [RenewalController::class, 'store'])->name('customers.renew');
    Route::post('customers/{customer}/complaints', [ComplaintController::class, 'store'])->name('customers.complaints.store');
    Route::post('complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])->name('complaints.status');
    Route::post('complaints/{complaint}/comments', [ComplaintController::class, 'addComment'])->name('complaints.comment');

    // Customer Notes & Promise-To-Pay
    Route::post('customers/{customer}/notes', [\App\Http\Controllers\CustomerNoteController::class, 'store'])->name('customers.notes.store');
    Route::post('customer-notes/{note}/status', [\App\Http\Controllers\CustomerNoteController::class, 'updateStatus'])->name('customers.notes.status');
});

// Staff Domain (/staff)
Route::middleware(['auth', 'role:admin,staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::redirect('/', '/staff/dashboard');
    Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/search', [StaffController::class, 'search'])->name('api.search');
    Route::get('/api/filtered-customers', [StaffController::class, 'filteredCustomers'])->name('api.filtered-customers');
    Route::get('/customers/{customer}', [StaffController::class, 'customerDetails'])->name('customer-details');
    Route::get('/complaints', [ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])->name('complaints.show');
    Route::get('/api/live-counts', [StaffController::class, 'liveCounts'])->name('api.live-counts');

    // Milestone 13: Offline PWA Sync Endpoints
    Route::get('/api/sync/bootstrap', [OfflineSyncController::class, 'bootstrapCache'])->name('sync.bootstrap');
    Route::post('/api/sync/mutations', [OfflineSyncController::class, 'processMutations'])->name('sync.mutations');
});

// Customer Portal Domain (/account) — Milestone 14
Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/invoices', [CustomerPortalController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/{invoice}', [CustomerPortalController::class, 'showInvoice'])->name('invoices.show');
    Route::get('/payments', [CustomerPortalController::class, 'payments'])->name('payments');
    Route::get('/receipts/{payment}', [CustomerPortalController::class, 'receipt'])->name('receipt');
    Route::get('/renewal', [CustomerPortalController::class, 'renewal'])->name('renewal');
    Route::post('/renewal', [CustomerPortalController::class, 'storeRenewal'])->name('renewal.store');
    Route::get('/complaints', [CustomerPortalController::class, 'complaints'])->name('complaints');
    Route::post('/complaints', [CustomerPortalController::class, 'storeComplaint'])->name('complaints.store');
    Route::post('/complaints/{complaint}/comments', [CustomerPortalController::class, 'commentComplaint'])->name('complaints.comment');
    Route::get('/profile', [CustomerPortalController::class, 'profile'])->name('profile');
    Route::get('/upgrade', [CustomerPortalController::class, 'upgrade'])->name('upgrade');
    Route::post('/upgrade', [CustomerPortalController::class, 'storeUpgrade'])->name('upgrade.store');
});

// Shared Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
