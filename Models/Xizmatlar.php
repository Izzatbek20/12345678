<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Xizmatlar extends Model
{
    use HasFactory;
    protected $table = 'xizmatlar';
    protected $primaryKey = 'xizmat_id';

    public function cleans(){
        $this->hasMany(Clean::class, 'clean_product', 'id');
    }
}
