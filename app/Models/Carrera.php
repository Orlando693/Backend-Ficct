<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    // ⚠️ Forzamos esquema + tabla para no depender de search_path:
    protected $table = 'academia.carreras';

    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = ['nombre', 'sigla', 'codigo', 'estado'];

    protected $casts = [
        'id_carrera' => 'integer',
        'estado' => 'string',
    ];
}
