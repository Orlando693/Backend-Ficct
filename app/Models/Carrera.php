<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $table = 'carreras';

    protected $fillable = [
        'nombre',
        'sigla',
        'estado', // 'ACTIVA' | 'INACTIVA'
    ];

    // Relaciones opcionales (si existen en tu BD)
    public function materias()
    {
        return $this->hasMany(Materia::class, 'carrera_id');
    }

    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'carrera_id');
    }
}
