<?php

namespace SoftwareBilling\Models;

use Illuminate\Database\Eloquent\Model;

class BillInformation extends Model
{
    protected $table = 'bill_information';

    protected $fillable = [
        'bill_type',
        'bill_id',
        'bill_month',
        'generate_date',
        'billing_start_date',
        'billing_end_date',
        'amount',
        'paid_amount',
        'due_amount',
        'bill_amount',
        'pre_due_bill_amount',
        'total_due_bill_amount',
        'total_bill_amount',
        'due_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'generate_date' => 'date',
        'billing_start_date' => 'date',
        'billing_end_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'bill_amount' => 'decimal:2',
        'pre_due_bill_amount' => 'decimal:2',
        'total_due_bill_amount' => 'decimal:2',
        'total_bill_amount' => 'decimal:2',
    ];
}
