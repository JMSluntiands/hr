<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentArchive extends Model
{
    protected $table = 'document_archive';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'deletion_requested_at' => 'datetime',
        'archived_at' => 'datetime',
    ];
}
