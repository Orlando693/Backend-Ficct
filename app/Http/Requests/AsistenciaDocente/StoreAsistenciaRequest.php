<?php

namespace App\Http\Requests\AsistenciaDocente;

use Illuminate\Foundation\Http\FormRequest;

class StoreAsistenciaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'gestion_id'     => ['required','integer'],
            'docente_id'     => ['nullable','integer'], // CPD/ADMIN lo envía; docente se fuerza a sí mismo
            'fecha'          => ['nullable','date'],
            'estado'         => ['required','in:presente,tarde,ausente,permiso'],
            'hora_ingreso'   => ['nullable','date_format:H:i'],
            'hora_salida'    => ['nullable','date_format:H:i'],
            'programacion_id'=> ['nullable','integer'],
            'fuente'         => ['nullable','in:docente,cpd,sistema'],
            'observacion'    => ['nullable','string','max:255'],
        ];
    }
}
