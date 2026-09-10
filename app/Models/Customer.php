<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'user_id',
        'area_id',
        'name',
        'status',
        'join_date',
        'billing_day',
        'balance',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'billing_day' => 'integer',
            'balance' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function primaryContact(): HasOne
    {
        return $this->hasOne(CustomerContact::class)->where('contact_type', 'primary');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function installationAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('address_type', 'installation');
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function packageHistories(): HasMany
    {
        return $this->hasMany(CustomerPackage::class);
    }
}
