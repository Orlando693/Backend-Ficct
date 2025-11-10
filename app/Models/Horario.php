<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    protected $connection = 'academia';
    protected $table = 'academia.horario';
    protected $primaryKey = 'id_horario';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $fillable = [
        'grupo_id',
        'aula_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'estado',
    ];
}
