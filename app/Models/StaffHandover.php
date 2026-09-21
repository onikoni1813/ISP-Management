<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffHandover extends Model
{
    use HasFactory, \App\Traits\FormatsSerializedDates;

    protected $fillable = [
        'handover_number',
        'staff_id',
        'handover_date',
        'cash_amount',
        'digital_amount',
        'total_amount',
        'collections_count',
        'status',
        'verified_by',
        'verified_at',
        'target_account_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cash_amount' => 'decimal:2',
            'digital_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'handover_date' => 'date:Y-m-d',
            'verified_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function targetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'target_account_id');
    }
}
