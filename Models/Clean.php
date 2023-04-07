<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clean extends Model
{
    use HasFactory;
    protected $table = 'clean';
    protected  $guarded = [];
    public $timestamps = false;
    public function orders()
    {
        $this->belongsTo(Order::class);
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }
    public function updateOrder($array = [])
    {
        return;
    }
    public function driver()
    {
        return  $this->hasOne(User::class, 'id', 'reclean_driver');
    }
    public function custumer()
    {
        return $this->hasOne(Costumers::class, 'id', 'costumer_id');
    }
    public function xizmat()
    {
        return $this->hasOne(Xizmatlar::class, 'xizmat_id', 'clean_product');
    }
    public function xizmatGilam()
    {
        return $this->hasMany(Xizmatlar::class, '')->where();
    }
    public function xodim()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function tokcha()
    {
        return $this->hasOne(Tokcha::class, 'name', 'joy');
    }
    public function qadoqladi()
    {
        return $this->hasMany(User::class, 'id', 'qad_user');
    }
    public function yuvdi()
    {
        return $this->hasMany(User::class, 'id', 'user_id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
    public function topshiruvchi()
    {
        return $this->hasOne(User::class, 'id', 'top_user');
    }
}
