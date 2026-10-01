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

    protected $appends = [
        'sms_rate',
    ];

    public function getSmsRateAttribute(): float
    {
        $customRate = $this->extra_params['sms_rate'] ?? null;
        if ($customRate !== null && is_numeric($customRate) && (float)$customRate > 0) {
            return (float)$customRate;
        }

        return match ($this->driver) {
            'bulksmsdhaka', 'alphasms', 'generic_http', 'bulksmsbd' => 0.30,
            default => 1.00,
        };
    }

    public function calculateRemainingSms(?string $balance): ?int
    {
        if ($balance === null || !is_numeric($balance)) {
            return null;
        }

        $rate = $this->sms_rate;
        if ($rate <= 0) {
            return null;
        }

        return (int) floor((float)$balance / $rate);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'gateway_id');
    }
}
