<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MijozKirim extends Model
{
    use HasFactory;
    protected $table = 'mijoz_kirim';

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
    public function order()
    {
        return $this->hasOne(Order::class,'order_id','order_id');
    }
}
