<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'speed_mbps',
        'description',
        'status',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(PackagePrice::class);
    }

    public function currentPrice(): HasOne
    {
        return $this->hasOne(PackagePrice::class)->where('status', 'active')->latestOfMany('effective_from');
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class, 'current_package_id');
    }

    public function customerPackages(): HasMany
    {
        return $this->hasMany(CustomerPackage::class);
    }
}
