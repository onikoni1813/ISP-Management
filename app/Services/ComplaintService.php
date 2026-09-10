<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintStatusHistory;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    /**
     * Create Complaint ticket.
     */
    public function createComplaint(Customer $customer, array $data, int $userId): Complaint
    {
        return DB::transaction(function () use ($customer, $data, $userId) {
            $lastComplaint = Complaint::lockForUpdate()->latest('id')->first();
            $nextNumber = $lastComplaint ? ($lastComplaint->id + 1) : 1;
            $complaintNumber = 'TKT-' . date('Y') . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);

            $primaryConnection = $customer->connections()->first();

            $status = !empty($data['assigned_to']) ? 'assigned' : 'open';

            $complaint = Complaint::create([
                'complaint_number' => $complaintNumber,
                'customer_id' => $customer->id,
                'connection_id' => $data['connection_id'] ?? $primaryConnection?->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => $status,
                'created_by' => $userId,
            ]);

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'old_status' => null,
                'new_status' => $status,
                'changed_by' => $userId,
                'note' => 'Ticket logged in system',
            ]);

            AuditLog::log('complaint_created', 'complaint', $complaint, null, $complaint->toArray());

            return $complaint;
        });
    }

    /**
     * Assign ticket to staff technician.
     */
    public function assignComplaint(Complaint $complaint, int $staffUserId, int $assignedBy): Complaint
    {
        return DB::transaction(function () use ($complaint, $staffUserId, $assignedBy) {
            $oldStatus = $complaint->status;
            $newStatus = 'assigned';

            $complaint->update([
                'assigned_to' => $staffUserId,
                'status' => $newStatus,
            ]);

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $assignedBy,
                'note' => "Assigned to Technician #{$staffUserId}",
            ]);

            AuditLog::log('complaint_assigned', 'complaint', $complaint, null, [
                'assigned_to' => $staffUserId,
                'assigned_by' => $assignedBy,
            ]);

            return $complaint;
        });
    }

    /**
     * Update status (e.g. In Progress, Resolved, Closed).
     */
    public function updateStatus(Complaint $complaint, string $newStatus, int $userId, ?string $resolutionNote = null): Complaint
    {
        return DB::transaction(function () use ($complaint, $newStatus, $userId, $resolutionNote) {
            $oldStatus = $complaint->status;

            $updateData = ['status' => $newStatus];

            if ($newStatus === 'resolved' || $newStatus === 'closed') {
                $updateData['resolved_by'] = $userId;
                $updateData['resolved_at'] = now();
                if ($resolutionNote) {
                    $updateData['resolution_note'] = $resolutionNote;
                }
            }

            $complaint->update($updateData);

            ComplaintStatusHistory::create([
                'complaint_id' => $complaint->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $userId,
                'note' => $resolutionNote ?? "Status changed from {$oldStatus} to {$newStatus}",
            ]);

            AuditLog::log('complaint_status_changed', 'complaint', $complaint, ['status' => $oldStatus], ['status' => $newStatus]);

            return $complaint;
        });
    }

    /**
     * Add comment to complaint.
     */
    public function addComment(Complaint $complaint, string $commentText, int $userId, bool $isInternal = false): ComplaintComment
    {
        return ComplaintComment::create([
            'complaint_id' => $complaint->id,
            'user_id' => $userId,
            'comment' => $commentText,
            'is_internal' => $isInternal,
        ]);
    }
}
