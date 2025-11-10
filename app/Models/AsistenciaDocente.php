<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaDocente extends Model
{
    protected $table = 'academia.asistencia_docente';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'gestion_id',
        'docente_id',
        'fecha',
        'estado',
        'hora_ingreso',
        'hora_salida',
        'programacion_id',
        'fuente',
        'observacion',
    ];

    public function docente()      { return $this->belongsTo(Persona::class,       'docente_id', 'id_persona'); }
    public function programacion() { return $this->belongsTo(Programacion::class,  'programacion_id', 'id'); }
}
