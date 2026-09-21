<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerNote extends Model
{
    use HasFactory, \App\Traits\FormatsSerializedDates;

    protected $fillable = [
        'customer_id',
        'user_id',
        'note_type',
        'note',
        'promise_date',
        'promise_amount',
        'status',
        'notify_admin',
    ];

    protected function casts(): array
    {
        return [
            'promise_date' => 'date:Y-m-d',
            'promise_amount' => 'decimal:2',
            'notify_admin' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
