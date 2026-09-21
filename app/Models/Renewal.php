<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renewal extends Model
{
    use HasFactory, \App\Traits\FormatsSerializedDates;

    protected $fillable = [
        'renewal_number',
        'customer_id',
        'connection_id',
        'package_id',
        'invoice_id',
        'payment_id',
        'previous_expiry',
        'new_expiry',
        'validity_days',
        'amount',
        'is_zero_charge',
        'renewal_type',
        'idempotency_key',
        'renewed_by',
        'renewed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'previous_expiry' => 'date:Y-m-d',
            'new_expiry' => 'date:Y-m-d',
            'validity_days' => 'integer',
            'amount' => 'decimal:2',
            'is_zero_charge' => 'boolean',
            'renewed_at' => 'datetime:Y-m-d h:i A',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function renewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
