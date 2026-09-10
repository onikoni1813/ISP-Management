<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class PppoeCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'connection_id',
        'username',
        'password',
        'service_name',
        'status',
    ];

    /**
     * Store password encrypted in database, decrypt on authorized retrieval.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Crypt::decryptString($value),
            set: fn (string $value) => Crypt::encryptString($value),
        );
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Connection::class);
    }
}
