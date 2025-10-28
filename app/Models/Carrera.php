<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    use HasFactory;

    // Tabla totalmente calificada con el esquema
    protected $table = 'academia.carreras';

    // Clave primaria real
    protected $primaryKey = 'carrera_id';

    protected $fillable = ['nombre', 'sigla', 'estado']; // 'ACTIVA' | 'INACTIVA'
}
