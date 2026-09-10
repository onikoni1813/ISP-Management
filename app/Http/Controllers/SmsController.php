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

        $logs = SmsLog::with(['customer', 'template', 'gateway', 'sender'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where('recipient', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $templates = SmsTemplate::all();
        $gateways = SmsGateway::all();

        return Inertia::render('Admin/Sms/Index', [
            'logs' => $logs,
            'templates' => $templates,
            'gateways' => $gateways,
            'filters' => $request->only(['status', 'search']),
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

        return Inertia::render('Admin/Sms/Gateways', [
            'gateways' => $gateways,
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
            'driver' => 'required|string|in:log,generic_http,greenweb,bulksmsbd',
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
}
