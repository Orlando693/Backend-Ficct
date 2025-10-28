<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $table = 'academia.bitacora';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'modulo', 'accion', 'descripcion', 'usuario', 'ip', 'created_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'created_at' => 'datetime',
    ];
}
