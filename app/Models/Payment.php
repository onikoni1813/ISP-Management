<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, \App\Traits\FormatsSerializedDates;

    protected $fillable = [
        'payment_number',
        'customer_id',
        'account_id',
        'amount',
        'payment_method',
        'reference',
        'idempotency_key',
        'paid_at',
        'collected_by',
        'status',
        'notes',
        'discount',
        'approved_by',
        'approved_at',
        'generated_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'paid_at' => 'datetime:Y-m-d h:i A',
            'approved_at' => 'datetime:Y-m-d h:i A',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function generatedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'generated_invoice_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
