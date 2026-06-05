<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $guarded = [];
}
