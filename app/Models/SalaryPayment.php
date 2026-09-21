<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use HasFactory, \App\Traits\FormatsSerializedDates;

    protected $fillable = [
        'payroll_number',
        'user_id',
        'salary_period_id',
        'account_id',
        'basic_salary',
        'bonus',
        'commission',
        'advance_deduction',
        'other_deductions',
        'net_salary',
        'payment_date',
        'status',
        'notes',
        'paid_by',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'bonus' => 'decimal:2',
            'commission' => 'decimal:2',
            'advance_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'payment_date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryPeriod(): BelongsTo
    {
        return $this->belongsTo(SalaryPeriod::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
