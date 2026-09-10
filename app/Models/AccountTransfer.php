<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'from_account_id',
        'to_account_id',
        'amount',
        'transfer_date',
        'notes',
        'transferred_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transfer_date' => 'date',
        ];
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }

    public function transferrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
