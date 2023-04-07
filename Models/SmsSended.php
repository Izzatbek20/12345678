<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSended extends Model
{
    use HasFactory;
    protected $table = 'sms_sended';
    protected $guarded = [];
}
