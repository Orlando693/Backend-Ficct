<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aula extends Model
{
    protected $table = 'academia.aula';
    protected $primaryKey = 'id_aula';
    public $timestamps = false;

    protected $fillable = [
        'codigo', 'tipo', 'capacidad', 'edificio_id', 'estado',
    ];

    protected $casts = [
        'capacidad' => 'integer',
    ];

    public function edificio() {
        return $this->belongsTo(\App\Models\Edificio::class, 'edificio_id');
    }
}
