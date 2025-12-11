<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscribeLog extends Model
{
    protected $table = 'subscribe_logs';

    protected $fillable = [
        'user_id',
        'email',
        'plan_id',
        'plan_name',
        'client_type',
        'ip',
        'location',
        'ua'
    ];
}
