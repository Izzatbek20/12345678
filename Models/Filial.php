<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Filial extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'filial';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function country()
    {
        return $this->hasOne(Country::class,'id','country_id');
    }
    public function kpiHisob()
    {
        return $this->hasMany(KpiHisob::class,'filial_id','filial_id');
    }
    public function getDavomat()
    {
        return $this->hasMany(Davomat::class,'filial_id','filial_id');
    }
    public function getMijozkirim()
    {
        return $this->hasMany(MijozKirim::class,'filial_id','filial_id');
    }
    public function getNasiya()
    {
        return $this->hasMany(Nasiya::class,'filial_id','filial_id');
    }
    public function getOrders()
    {
        return $this->hasMany(Order::class,'order_filial_id','filial_id');
    }
}
