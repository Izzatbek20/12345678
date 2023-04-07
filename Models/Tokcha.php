<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tokcha extends Model
{
    use HasFactory;
    protected $table = 'tokchalar';
    protected $guarded = [];
}
