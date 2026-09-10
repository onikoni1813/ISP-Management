<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Connection extends Model
{
    use HasFactory;

    protected $fillable = [
        'connection_code',
        'customer_id',
        'area_id',
        'current_package_id',
        'protocol',
        'ip_address',
        'mac_address',
        'router_model',
        'fiber_box_id',
        'status',
        'installation_date',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function currentPackage(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'current_package_id');
    }

    public function pppoeCredential(): HasOne
    {
        return $this->hasOne(PppoeCredential::class);
    }

    public function packageHistories(): HasMany
    {
        return $this->hasMany(CustomerPackage::class);
    }
}
