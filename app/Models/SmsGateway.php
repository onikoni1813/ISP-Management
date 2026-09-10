<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'driver',
        'api_url',
        'api_key',
        'sender_id',
        'extra_params',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'extra_params' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'gateway_id');
    }
}
