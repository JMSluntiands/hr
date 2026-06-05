<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'additional_performance_review',
    ];

    protected $casts = [
        'additional_performance_review' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
