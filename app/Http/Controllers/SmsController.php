<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsGateway;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SmsController extends Controller
{
    public function __construct(
        protected SmsService $smsService
    ) {}

    /**
     * SMS Dashboard, Log Ledger & Manual Sender.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('sms.view');

        $query = SmsLog::with(['customer', 'template', 'gateway', 'sender'])
            ->when($request->status, function ($q, $status) {
                if ($status === 'sent') {
                    $q->whereIn('status', ['sent', 'delivered']);
                } else {
                    $q->where('status', $status);
                }
            })
            ->when($request->gateway_id, fn($q, $gwId) => $q->where('gateway_id', $gwId))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('recipient', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id');

        $logs = $query->paginate(20)->withQueryString();

        // Financial & Dispatch metrics
        $today = now()->startOfDay();
        $metrics = [
            'total_sent' => SmsLog::whereIn('status', ['sent', 'delivered'])->count(),
            'total_failed' => SmsLog::where('status', 'failed')->count(),
            'today_dispatched' => SmsLog::where('created_at', '>=', $today)->count(),
            'total_logs' => SmsLog::count(),
        ];

        $templates = SmsTemplate::all();
        $gateways = SmsGateway::all();
        $activeGateway = $gateways->firstWhere('is_active', true);
        $initialBalance = null;
        if ($activeGateway && cache()->has('sms_active_balance_' . $activeGateway->id)) {
            $initialBalance = cache()->get('sms_active_balance_' . $activeGateway->id);
        }

        return Inertia::render('Admin/Sms/Index', [
            'logs' => $logs,
            'templates' => $templates,
            'gateways' => $gateways,
            'activeGateway' => $activeGateway,
            'initialBalance' => $initialBalance,
            'metrics' => $metrics,
            'filters' => $request->only(['status', 'search', 'gateway_id']),
        ]);
    }

    /**
     * Send Manual / Custom SMS.
     */
    public function sendManual(Request $request)
    {
        Gate::authorize('sms.send');

        $validated = $request->validate([
            'recipient' => 'required|string|min:11|max:20',
            'message' => 'required|string|max:480',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $log = $this->smsService->sendSms(
            $validated['recipient'],
            $validated['message'],
            $request->user()->id,
            $validated['customer_id'] ?? null
        );

        if ($log->status === 'sent') {
            return back()->with('success', "SMS successfully dispatched to {$log->recipient}.");
        }

        return back()->with('error', "SMS dispatch failed: {$log->error_message}");
    }

    /**
     * Send Bulk / Targeted SMS to selected customers or whole filtered set.
     */
    public function sendBulk(Request $request)
    {
        Gate::authorize('sms.send');

        $validated = $request->validate([
            'message' => 'required|string|max:480',
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'exists:customers,id',
            'target_all_filtered' => 'nullable|boolean',
            'search' => 'nullable|string',
            'status' => 'nullable|string',
            'area_id' => 'nullable|integer',
            'advanced_filter' => 'nullable|string',
        ]);

        $query = Customer::query()->with(['primaryContact', 'connections.currentPackage', 'connections.pppoeCredential']);

        if (!empty($validated['target_all_filtered'])) {
            $today = now()->toDateString();
            $in3Days = now()->addDays(3)->toDateString();
            $in7Days = now()->addDays(7)->toDateString();

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }
            if (!empty($validated['area_id'])) {
                $query->where('area_id', $validated['area_id']);
            }
            if (!empty($validated['advanced_filter'])) {
                $af = $validated['advanced_filter'];
                if ($af === 'expiring_3d') {
                    $query->whereHas('connections', fn($c) => $c->whereBetween('expiry_date', [$today, $in3Days]));
                } elseif ($af === 'expiring_7d') {
                    $query->whereHas('connections', fn($c) => $c->whereBetween('expiry_date', [$today, $in7Days]));
                } elseif ($af === 'expired') {
                    $query->whereHas('connections', fn($c) => $c->whereNotNull('expiry_date')->where('expiry_date', '<', $today));
                } elseif ($af === 'due') {
                    $query->where(function ($sub) {
                        $sub->where('balance', '>', 0)
                            ->orWhereHas('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
                    });
                } elseif ($af === 'zero_charge_renewed') {
                    $query->whereHas('renewals', function ($r) {
                        $r->where('is_zero_charge', true)
                          ->whereRaw('renewals.renewed_at >= COALESCE((SELECT MAX(p.paid_at) FROM payments p WHERE p.customer_id = renewals.customer_id), "1970-01-01")');
                    });
                } elseif ($af === 'paid_this_month') {
                    $startOfMonth = now()->startOfMonth()->toDateTimeString();
                    $endOfMonth = now()->endOfMonth()->toDateTimeString();
                    $query->whereHas('payments', function ($p) use ($startOfMonth, $endOfMonth) {
                        $p->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                          ->where('status', 'completed');
                    })->where(function ($sub) {
                        $sub->where('balance', '<=', 0)
                            ->whereDoesntHave('invoices', fn($inv) => $inv->where('due_amount', '>', 0));
                    });
                }
            }
            if (!empty($validated['search'])) {
                $s = $validated['search'];
                $query->where(function ($sub) use ($s) {
                    $sub->where('customer_code', 'like', "%{$s}%")
                        ->orWhere('name', 'like', "%{$s}%")
                        ->orWhereHas('contacts', fn($c) => $c->where('phone', 'like', "%{$s}%"));
                });
            }
        } elseif (!empty($validated['customer_ids'])) {
            $query->whereIn('id', $validated['customer_ids']);
        } else {
            return back()->with('error', 'কোন গ্রাহক নির্বাচন করা হয়নি।');
        }

        $customers = $query->get();
        if ($customers->isEmpty()) {
            return back()->with('error', 'মেসেজ পাঠানোর মতো কোনো উপযুক্ত গ্রাহক পাওয়া যায়নি।');
        }

        $sentCount = 0;
        $failedCount = 0;
        $userId = $request->user()?->id;

        foreach ($customers as $customer) {
            $phone = $customer->primaryContact?->phone;
            if (!$phone) {
                $failedCount++;
                continue;
            }

            // Interpolate dynamic tags
            $firstConn = $customer->connections->first();
            $expiry = $firstConn?->expiry_date ? \Carbon\Carbon::parse($firstConn->expiry_date)->format('d M Y') : 'N/A';
            $pkg = $firstConn?->currentPackage?->name ?? 'Package';
            $due = abs($customer->balance < 0 ? $customer->balance : 0);
            $pppoe = $firstConn?->pppoeCredential;

            $personalizedMsg = $this->smsService->parseTemplate($validated['message'], [
                'name' => $customer->name,
                'code' => $customer->customer_code,
                'customer_code' => $customer->customer_code,
                'expiry_date' => $expiry,
                'package' => $pkg,
                'due_amount' => $due,
                'due' => number_format((float) $due, 2),
                'pppoe_username' => $pppoe?->username ?? 'N/A',
                'pppoe_password' => $pppoe?->password ?? 'N/A',
                'login_url' => url('/login'),
            ]);

            try {
                $log = $this->smsService->sendSms($phone, $personalizedMsg, $userId, $customer->id, null, 'bulk_campaign');
                if ($log->status === 'sent') {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }
        }

        return back()->with('success', "বাল্ক এসএমএস সম্পন্ন! সফল: {$sentCount} টি, ব্যর্থ/ফোনহীন: {$failedCount} টি।");
    }

    /**
     * SMS Templates List & Editor.
     */
    public function templates(): Response
    {
        Gate::authorize('sms.templates');

        $templates = SmsTemplate::latest('id')->get();

        return Inertia::render('Admin/Sms/Templates', [
            'templates' => $templates,
        ]);
    }

    /**
     * Update SMS Template.
     */
    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        Gate::authorize('sms.templates');

        $validated = $request->validate([
            'template' => 'required|string|max:500',
            'is_auto_enabled' => 'required|boolean',
        ]);

        $template->update($validated);

        return back()->with('success', "Template '{$template->name}' updated successfully.");
    }

    /**
     * SMS Gateways Configuration.
     */
    public function gateways(): Response
    {
        Gate::authorize('sms.settings');

        $gateways = SmsGateway::all();
        $balanceInfo = $this->smsService->getBalance();

        return Inertia::render('Admin/Sms/Gateways', [
            'gateways' => $gateways,
            'balanceInfo' => $balanceInfo,
        ]);
    }

    /**
     * Store or Update SMS Gateway.
     */
    public function storeGateway(Request $request)
    {
        Gate::authorize('sms.settings');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'driver' => 'required|string|in:log,generic_http,greenweb,bulksmsbd,alphasms,bdbulksms,bulksmsdhaka',
            'api_url' => 'nullable|string|url',
            'api_key' => 'nullable|string|max:255',
            'sender_id' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        if ($validated['is_active']) {
            SmsGateway::query()->update(['is_active' => false]);
        }

        SmsGateway::create($validated);

        return back()->with('success', "Gateway '{$validated['name']}' saved successfully.");
    }

    /**
     * Update an existing SMS Gateway.
     */
    public function updateGateway(Request $request, SmsGateway $gateway)
    {
        Gate::authorize('sms.settings');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'driver' => 'required|string|in:log,generic_http,greenweb,bulksmsbd,alphasms,bdbulksms,bulksmsdhaka',
            'api_url' => 'nullable|string|url',
            'api_key' => 'nullable|string|max:255',
            'sender_id' => 'nullable|string|max:50',
            'is_active' => 'required|boolean',
        ]);

        if ($validated['is_active']) {
            SmsGateway::where('id', '!=', $gateway->id)->update(['is_active' => false]);
        }

        if (empty($validated['api_key'])) {
            unset($validated['api_key']);
        }

        $gateway->update($validated);

        return back()->with('success', "Gateway '{$gateway->name}' updated successfully.");
    }

    /**
     * Delete an SMS Gateway.
     */
    public function destroyGateway(SmsGateway $gateway)
    {
        Gate::authorize('sms.settings');

        $wasActive = $gateway->is_active;
        $name = $gateway->name;

        // Disassociate existing logs from deleted gateway before delete
        SmsLog::where('gateway_id', $gateway->id)->update(['gateway_id' => null]);

        $gateway->delete();

        // If the deleted gateway was active, fall back to default log gateway or first available
        if ($wasActive) {
            $fallback = SmsGateway::first();
            if ($fallback) {
                $fallback->update(['is_active' => true]);
            } else {
                SmsGateway::create([
                    'name' => 'System Log (Testing)',
                    'driver' => 'log',
                    'is_active' => true,
                ]);
            }
        }

        return back()->with('success', "Gateway '{$name}' has been deleted successfully.");
    }

    /**
     * Activate a Gateway.
     */
    public function activateGateway(SmsGateway $gateway)
    {
        Gate::authorize('sms.settings');

        SmsGateway::query()->update(['is_active' => false]);
        $gateway->update(['is_active' => true]);

        return back()->with('success', "Gateway '{$gateway->name}' is now active.");
    }

    /**
     * Test SMS sending via specific Gateway.
     */
    public function testGateway(Request $request, SmsGateway $gateway)
    {
        Gate::authorize('sms.settings');

        $validated = $request->validate([
            'recipient' => 'required|string|regex:/^(\+?88)?01[3-9]\d{8}$/',
            'message' => 'required|string|max:160',
        ]);

        $driver = $this->smsService->resolveDriver($gateway->driver);
        $result = $driver->send($validated['recipient'], $validated['message'], $gateway);

        SmsLog::create([
            'recipient' => $validated['recipient'],
            'customer_id' => null,
            'template_id' => null,
            'gateway_id' => $gateway->id,
            'message' => $validated['message'],
            'status' => $result['success'] ? 'sent' : 'failed',
            'provider_message_id' => $result['message_id'] ?? null,
            'error_message' => $result['error'] ?? null,
            'sent_at' => $result['success'] ? now() : null,
            'sent_by' => $request->user()?->id,
            'entity_type' => 'test_sms',
            'entity_id' => $gateway->id,
        ]);

        if ($result['success']) {
            return back()->with('success', "Test SMS sent successfully via {$gateway->name}. Reference ID: {$result['message_id']}");
        }

        return back()->withErrors(['test_error' => "SMS Dispatch Failed: {$result['error']}"]);
    }

    /**
     * Check live balance for specific Gateway.
     */
    public function checkBalance(SmsGateway $gateway)
    {
        if (!Gate::allows('sms.view') && !Gate::allows('sms.send') && !Gate::allows('sms.settings')) {
            abort(403);
        }

        $balance = $this->smsService->getBalance($gateway);

        if (!empty($balance['success'])) {
            cache()->put('sms_active_balance_' . $gateway->id, [
                'success' => true,
                'gateway' => [
                    'id' => $gateway->id,
                    'name' => $gateway->name,
                    'driver' => $gateway->driver,
                    'sender_id' => $gateway->sender_id,
                ],
                'balance' => $balance['balance'] ?? null,
                'error' => null,
                'checked_at' => now()->format('h:i:s A'),
            ], now()->addMinutes(5));
        }

        return response()->json($balance);
    }

    /**
     * Check live balance for the active or requested Gateway.
     */
    public function getActiveBalance(Request $request)
    {
        if (!Gate::allows('sms.view') && !Gate::allows('sms.send') && !Gate::allows('sms.settings')) {
            abort(403);
        }

        $gatewayId = $request->query('gateway_id');
        $gateway = $gatewayId ? SmsGateway::find($gatewayId) : SmsGateway::where('is_active', true)->first();

        if (!$gateway) {
            return response()->json([
                'success' => false,
                'gateway' => null,
                'balance' => null,
                'error' => 'কোন সক্রিয় SMS গেটওয়ে কনফিগার করা নেই।',
            ]);
        }

        $force = $request->boolean('force', false);
        $cacheKey = 'sms_active_balance_' . $gateway->id;

        if (!$force && cache()->has($cacheKey)) {
            return response()->json(cache()->get($cacheKey));
        }

        $result = $this->smsService->getBalance($gateway);
        $payload = [
            'success' => $result['success'] ?? false,
            'gateway' => [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'driver' => $gateway->driver,
                'sender_id' => $gateway->sender_id,
            ],
            'balance' => $result['balance'] ?? null,
            'error' => $result['error'] ?? null,
            'checked_at' => now()->format('h:i:s A'),
        ];

        if (!empty($result['success'])) {
            cache()->put($cacheKey, $payload, now()->addMinutes(5));
        }

        return response()->json($payload);
    }

    /**
     * Retry Failed SMS.
     */
    public function retry(SmsLog $log, Request $request)
    {
        Gate::authorize('sms.send');

        $this->smsService->retrySms($log, $request->user()->id);

        if ($log->fresh()->status === 'sent') {
            return back()->with('success', "SMS retried successfully.");
        }

        return back()->with('error', "Retry attempt failed: {$log->fresh()->error_message}");
    }

    /**
     * Delete a single SMS dispatch log.
     */
    public function destroyLog(SmsLog $log)
    {
        Gate::authorize('sms.view');

        $log->delete();

        return back()->with('success', 'SMS লগ সফলভাবে ডিলিট করা হয়েছে।');
    }

    /**
     * Bulk delete selected or all filtered SMS logs.
     */
    public function bulkDestroyLogs(Request $request)
    {
        Gate::authorize('sms.view');

        $validated = $request->validate([
            'log_ids' => ['nullable', 'array'],
            'log_ids.*' => ['integer', 'exists:sms_logs,id'],
            'target_all_filtered' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'gateway_id' => ['nullable'],
        ]);

        if (!empty($validated['target_all_filtered'])) {
            $query = SmsLog::query();

            if (!empty($validated['status'])) {
                if ($validated['status'] === 'sent') {
                    $query->whereIn('status', ['sent', 'delivered']);
                } else {
                    $query->where('status', $validated['status']);
                }
            }

            if (!empty($validated['gateway_id'])) {
                $query->where('gateway_id', $validated['gateway_id']);
            }

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $query->where(function ($sub) use ($search) {
                    $sub->where('recipient', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($c) use ($search) {
                            $c->where('name', 'like', "%{$search}%")
                              ->orWhere('customer_code', 'like', "%{$search}%");
                        });
                });
            }

            $count = $query->count();
            $query->delete();

            return back()->with('success', "ফিল্টারকৃত সর্বমোট {$count} টি SMS লগ সফলভাবে ডিলিট করা হয়েছে।");
        }

        $ids = $validated['log_ids'] ?? [];
        if (empty($ids)) {
            return back()->with('error', 'ডিলিট করার জন্য কোনো SMS সিলেক্ট করা হয়নি।');
        }

        $count = SmsLog::whereIn('id', $ids)->delete();

        return back()->with('success', "সিলেক্ট করা {$count} টি SMS লগ সফলভাবে ডিলিট করা হয়েছে।");
    }
}

