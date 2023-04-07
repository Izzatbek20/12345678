<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nasiya extends Model
{
    use HasFactory;
    protected  $guarded = [];
    protected $table = 'nasiya';


    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function staff()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
    public function nasiyachi()
    {
        return $this->hasOne(Costumers::class,'id','nasiyachi_id');
    }
}
