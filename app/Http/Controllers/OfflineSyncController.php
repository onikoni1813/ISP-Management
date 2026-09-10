<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Complaint;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Renewal;
use App\Services\BillingService;
use App\Services\ComplaintService;
use App\Services\RenewalService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class OfflineSyncController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected RenewalService $renewalService,
        protected ComplaintService $complaintService,
    ) {}

    /**
     * Download comprehensive offline cache dataset for field staff PWA.
     * Caches: Customers, Packages, Areas, and open Complaints.
     */
    public function bootstrapCache(Request $request): JsonResponse
    {
        Gate::authorize('customers.view');

        $user = $request->user();
        $isStaffOnly = $user->hasRole('staff') && !$user->hasRole('admin');

        // 1. Customers with relations needed for offline operations
        $customers = Customer::with([
            'primaryContact',
            'installationAddress',
            'area:id,name,code',
            'connections.currentPackage.currentPrice',
            'connections.pppoeCredential',
        ])
        ->whereIn('status', ['active', 'expired', 'suspended'])
        ->get()
        ->map(function ($c) use ($user) {
            $primaryConn = $c->connections->first();
            $pppoe = $primaryConn?->pppoeCredential;

            return [
                'id' => $c->id,
                'customer_code' => $c->customer_code,
                'name' => $c->name,
                'status' => $c->status,
                'balance' => (float) $c->balance,
                'phone' => $c->primaryContact?->phone ?? '',
                'address' => $c->installationAddress?->full_address ?? '',
                'area_name' => $c->area?->name ?? '',
                'connection_id' => $primaryConn?->id,
                'package_name' => $primaryConn?->currentPackage?->name ?? 'Standard',
                'speed_mbps' => $primaryConn?->currentPackage?->speed_mbps ?? 0,
                'monthly_rate' => (float) ($primaryConn?->currentPackage?->currentPrice?->price ?? 0),
                'expiry_date' => $primaryConn?->expiry_date?->toDateString() ?? 'N/A',
                'pppoe_username' => $pppoe?->username ?? '',
                // Master Plan Rule 8 & 40: PPPoE password is only provided if permitted
                'can_view_pppoe' => Gate::forUser($user)->allows('pppoe.view_password'),
                'updated_at' => $c->updated_at?->toIso8601String(),
            ];
        });

        // 2. Packages with active pricing
        $packages = Package::with('currentPrice')
            ->where('is_active', true)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'speed_mbps' => $p->speed_mbps,
                'price' => (float) ($p->currentPrice?->price ?? 0),
                'validity_days' => $p->currentPrice?->validity_days ?? 30,
            ]);

        // 3. Areas
        $areas = Area::where('status', 'active')
            ->select('id', 'name', 'code')
            ->get();

        // 4. Complaints (Assigned to this staff or all open)
        $complaintsQuery = Complaint::with(['customer:id,name,customer_code', 'assignee:id,name'])
            ->whereIn('status', ['open', 'assigned', 'in_progress']);

        if ($isStaffOnly) {
            $complaintsQuery->where('assigned_to', $user->id);
        }

        $complaints = $complaintsQuery->get()->map(fn($t) => [
            'id' => $t->id,
            'complaint_number' => $t->complaint_number,
            'customer_id' => $t->customer_id,
            'customer_name' => $t->customer?->name,
            'customer_code' => $t->customer?->customer_code,
            'subject' => $t->subject,
            'description' => $t->description,
            'priority' => $t->priority,
            'status' => $t->status,
            'assigned_to' => $t->assigned_to,
            'assigned_name' => $t->assignee?->name ?? 'Unassigned',
            'created_at' => $t->created_at?->toIso8601String(),
            'updated_at' => $t->updated_at?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'server_version' => 1,
            'data' => [
                'customers' => $customers,
                'packages' => $packages,
                'areas' => $areas,
                'complaints' => $complaints,
            ],
        ]);
    }

    /**
     * Process batch offline mutations queued in IndexedDB.
     * Adheres to Rule 6 (idempotency), Rule 7 (server authorization), and Section 39 (conflict handling).
     */
    public function processMutations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mutations' => 'required|array',
            'mutations.*.uuid' => 'required|string|max:100',
            'mutations.*.action' => 'required|string|in:collect_payment,renew_connection,create_complaint,update_complaint_status',
            'mutations.*.payload' => 'required|array',
            'mutations.*.timestamp' => 'required|string',
        ]);

        $results = [];
        $user = $request->user();

        foreach ($validated['mutations'] as $mut) {
            $uuid = $mut['uuid'];
            $action = $mut['action'];
            $payload = $mut['payload'];

            try {
                switch ($action) {
                    case 'collect_payment':
                        Gate::authorize('billing.collect');
                        $customer = Customer::findOrFail($payload['customer_id']);

                        $payload['idempotency_key'] = $uuid;
                        $payment = $this->billingService->collectPayment($customer, $payload, $user->id);

                        $results[] = [
                            'uuid' => $uuid,
                            'status' => 'success',
                            'entity_id' => $payment->id,
                            'reference' => $payment->payment_number,
                            'message' => "Payment {$payment->payment_number} processed successfully.",
                        ];
                        break;

                    case 'renew_connection':
                        Gate::authorize('renewals.create');
                        $customer = Customer::findOrFail($payload['customer_id']);
                        $connection = $customer->connections()->findOrFail($payload['connection_id']);

                        $payload['idempotency_key'] = $uuid;
                        $renewal = $this->renewalService->processRenewal($customer, $connection, $payload, $user->id);

                        $results[] = [
                            'uuid' => $uuid,
                            'status' => 'success',
                            'entity_id' => $renewal->id,
                            'reference' => $renewal->renewal_number,
                            'message' => "Renewal {$renewal->renewal_number} recorded. Expiry: {$renewal->new_expiry}",
                        ];
                        break;

                    case 'create_complaint':
                        Gate::authorize('complaints.create');
                        $customer = Customer::findOrFail($payload['customer_id']);

                        $complaint = $this->complaintService->createComplaint($customer, $payload, $user->id);

                        $results[] = [
                            'uuid' => $uuid,
                            'status' => 'success',
                            'entity_id' => $complaint->id,
                            'reference' => $complaint->complaint_number,
                            'message' => "Ticket {$complaint->complaint_number} created.",
                        ];
                        break;

                    case 'update_complaint_status':
                        Gate::authorize('complaints.update');
                        $complaint = Complaint::findOrFail($payload['complaint_id']);

                        // Conflict detection: If ticket was already resolved/closed by someone else
                        if (in_array($complaint->status, ['resolved', 'closed']) && $payload['status'] !== $complaint->status) {
                            $results[] = [
                                'uuid' => $uuid,
                                'status' => 'conflict',
                                'entity_id' => $complaint->id,
                                'message' => "Conflict: Ticket #{$complaint->complaint_number} is already {$complaint->status}.",
                            ];
                            break;
                        }

                        $updated = $this->complaintService->updateStatus(
                            $complaint,
                            $payload['status'],
                            $user->id,
                            $payload['note'] ?? 'Updated via field PWA'
                        );

                        $results[] = [
                            'uuid' => $uuid,
                            'status' => 'success',
                            'entity_id' => $updated->id,
                            'reference' => $updated->complaint_number,
                            'message' => "Ticket #{$updated->complaint_number} status updated to {$updated->status}.",
                        ];
                        break;

                    default:
                        $results[] = [
                            'uuid' => $uuid,
                            'status' => 'error',
                            'message' => 'Unknown mutation action.',
                        ];
                }
            } catch (Exception $e) {
                Log::warning("Offline mutation failed for UUID {$uuid}: " . $e->getMessage());
                $results[] = [
                    'uuid' => $uuid,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'processed_count' => count($results),
            'results' => $results,
        ]);
    }
}
