<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeLog extends Model
{
    protected $table = 'subscribe_logs';
    protected $dateFormat = 'U';
    protected $guarded = ['id'];
    protected $casts = [
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];
    
}
