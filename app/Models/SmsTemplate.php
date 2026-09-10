<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'template',
        'is_auto_enabled',
    ];

    protected function casts(): array
    {
        return [
            'is_auto_enabled' => 'boolean',
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'template_id');
    }
}
