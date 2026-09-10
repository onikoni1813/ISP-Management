<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient',
        'customer_id',
        'template_id',
        'gateway_id',
        'message',
        'status',
        'provider_message_id',
        'error_message',
        'sent_at',
        'sent_by',
        'entity_type',
        'entity_id',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'template_id');
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(SmsGateway::class, 'gateway_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
