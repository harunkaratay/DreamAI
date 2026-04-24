<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DreamLog extends Model
{
    protected $fillable = [
        'user_id',
        'dream_text',
        'analysis_text',
        'images',
    ];
}
