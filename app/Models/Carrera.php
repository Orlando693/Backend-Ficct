<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'academia.carreras'; // fuerza esquema.tabla
    protected $primaryKey = 'id_carrera';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'sigla', 'codigo', 'estado',
    ];
}
