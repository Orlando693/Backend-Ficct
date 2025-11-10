<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programacion extends Model
{
    protected $table = 'academia.programacion';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $guarded = [];
}
