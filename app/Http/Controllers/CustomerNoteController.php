<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerNote;
use Illuminate\Http\Request;

class CustomerNoteController extends Controller
{
    /**
     * Store a note / promise-to-pay for customer.
     */
    public function store(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'note' => 'required|string|max:1000',
            'note_type' => 'required|string|in:promise_to_pay,issue_report,general_remark',
            'promise_date' => 'nullable|date',
            'promise_amount' => 'nullable|numeric|min:0',
        ]);

        $note = CustomerNote::create([
            'customer_id' => $customer->id,
            'user_id' => $request->user()->id,
            'note_type' => $validated['note_type'],
            'note' => $validated['note'],
            'promise_date' => $validated['promise_date'] ?? null,
            'promise_amount' => $validated['promise_amount'] ?? null,
            'status' => 'pending',
            'notify_admin' => true,
        ]);

        AuditLog::log('customer_note_created', 'crm', $customer, null, [
            'note_id' => $note->id,
            'note_type' => $note->note_type,
            'promise_date' => $note->promise_date,
            'note' => $note->note,
            'created_by' => $request->user()->name,
        ]);

        return back()->with('success', 'নোট / পরবর্তী বিল দেওয়ার তারিখ সফলভাবে সংরক্ষণ করা হয়েছে।');
    }

    /**
     * Toggle or update note status (e.g. resolved / done).
     */
    public function updateStatus(Request $request, CustomerNote $note)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,resolved,cancelled',
        ]);

        $note->update([
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'নোট স্ট্যাটাস আপডেট হয়েছে।');
    }
}
