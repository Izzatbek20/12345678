<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    //    protected  $table = 'order';

    protected $primaryKey = 'order_id';
    protected $guarded = [];
    protected $hidden = [];
    protected $casts = [
        'costumer_name'
    ];
    public $timestamps = false;
    protected $appends = [];
    // public function costumer()
    // {
    //     return $this->belongsTo(Costumers::class, 'id', 'costumer_id');
    // }
    public function custumer()
    {
        return $this->hasOne(Costumers::class, 'id', 'costumer_id');
    }
    public function cleans()
    {
        return $this->hasMany(Clean::class, 'order_id', 'order_id');
    }
    public function cleansSum()
    {
        return $this->hasMany(Clean::class, 'id', 'order_id')->sum('clean_narx');
    }
    public function cleanCount()
    {
        return $this->hasMany(Clean::class, 'id', 'order_id')->count();
    }
    public function operator()
    {
        return $this->hasOne(User::class, 'id', 'operator_id');
    }
    public function transport()
    {
        return $this->hasOne(User::class, 'id', 'operator_id');
    }
    public function ombor()
    {
        return $this->hasOne(User::class, 'id', 'ombor_user');
    }
    public function finishdriver()
    {
        return $this->hasOne(User::class, 'id', 'operator_id');
    }
    public function driver()
    {
        return $this->hasOne(User::class, 'id', 'order_driver');
    }
    public function mijozkirim()
    {
        return $this->hasOne(MijozKirim::class, 'order_id', 'order_id');
    }
    public function mijozkirims()
    {
        return $this->hasMany(MijozKirim::class, 'order_id', 'order_id')->where('status', '=', 'olindi');
    }
    public function buyurtmas()
    {
        return $this->hasMany(Buyurtma::class, 'order_id', 'order_id');
    }
    public function naqd()
    {
        return $this->hasOne(MijozKirim::class, 'order_id', 'order_id')
            ->where('status', '=', 'olindi')
            ->where('tolov_turi', '=', 'naqd');
    }
    public function click()
    {
        return $this->hasOne(MijozKirim::class, 'order_id', 'order_id')
            ->where(['status' => 'olindi', 'tolov_turi' => 'click']);
    }
    public function terminal()
    {
        return $this->hasOne(MijozKirim::class, 'order_id', 'order_id')
            ->where(['status' => 'olindi', 'tolov_turi' => 'Terminal-bank']);
    }
    public function kechildi()
    {
        return $this->hasOne(MijozKirim::class, 'order_id', 'order_id')
            ->where(['status' => 'olindi', 'tolov_turi' => 'kechildi']);
    }
    public function nasiya()
    {
        return $this->hasMany(Nasiya::class, 'order_id', 'order_id');
    }
    public function chegirma()
    {
        return $this->hasOne(Chegirma::class, 'order_id', 'order_id');
    }
}
