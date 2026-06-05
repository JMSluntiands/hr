<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reimbursement extends Model
{
    protected $table = 'reimbursements';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'purchased_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'reimbursed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
