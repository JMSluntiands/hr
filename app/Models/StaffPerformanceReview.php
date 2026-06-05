<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffPerformanceReview extends Model
{
    protected $table = 'staff_performance_reviews';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'review_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
