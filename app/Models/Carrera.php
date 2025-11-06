<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    // Esquema explicito para instalaciones sin search_path ajustado.
    protected $table = 'academia.carreras';

    protected $fillable = ['nombre', 'sigla', 'estado'];

    protected $casts = [
        'id' => 'integer',
        'estado' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
