<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPackage extends Model
{
    use HasFactory, \App\Traits\FormatsSerializedDates;

    protected $fillable = [
        'customer_id',
        'connection_id',
        'package_id',
        'actual_price',
        'start_date',
        'end_date',
        'assigned_by',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'actual_price' => 'decimal:2',
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
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

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
