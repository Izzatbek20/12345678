<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Calls;


class Costumers extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $hidden = [];

    public function orders()
    {
        return $this->hasMany(Order::class,'costumer_id','id');
    }
    public function ordersId()
    {
        return $this->hasMany(Order::class,'costumer_id','id')->select(['order_id']);
    }
    public function cleans()
    {
        return $this->hasManyThrough(Clean::class,Order::class,'id','order_id');
    }
    public function cleanCount()
    {
        return $this->hasManyThrough(Clean::class,Order::class,'id','order_id','order_id','id');
    }
    public function nasiya()
    {
        return $this->hasMany(Nasiya::class,'nasiyachi_id','id');
    }
    public function pullar()
    {
        return $this->hasMany(MijozKirim::class,'costumer_id','id')->orderBy('date','ASC');
    }
    public function calls()
    {
        return $this->hasMany(Calls::class,'costumer_id','id');
    }
    public function millat()
    {
        return $this->hasOne(Millat::class,'id','millat_id');
    }
}
