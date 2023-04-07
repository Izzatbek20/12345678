<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiHisob extends Model
{
    use HasFactory;
    protected $table = 'kpi_hisob';

    protected $guarded = [];
    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
}
