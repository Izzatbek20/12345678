<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    //    protected $fillable = [
    //        'username',
    //        'email',
    //        'password',
    //    ];

    protected  $guarded = [];
    protected $table = 'user';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function filial()
    {
        return $this->hasOne(Filial::class, 'filial_id', 'filial_id');
    }
    public function operatorOrders()
    {
        return $this->belongsToMany(Order::class, 'user', 'id', 'order_driver', 'id', 'id');
    }
    public function ord()
    {
        return $this->hasMany(Order::class, 'operator_id', 'id')->where('order_status', '!=', 'bekor qilindi');
    }
    public function nasiya()
    {
        return $this->hasMany(Nasiya::class, 'user_id', 'id');
    }
    public function getXizmatlar()
    {
        return $this->hasMany(Xizmatlar::class, 'filial_id', 'filial_id');
    }
}
