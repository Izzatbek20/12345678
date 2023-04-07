<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chegirma extends Model
{
    use HasFactory;
    protected $table = 'chegirma';
    protected $guarded =[];

    public function xizmat()
    {
        return $this->hasOne(Xizmatlar::class,'xizmat_id','xizmat_id');
    }
}
