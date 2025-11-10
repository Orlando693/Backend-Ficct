<?php

namespace App\Http\Requests\AsistenciaDocente;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAsistenciaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'gestion_id'     => ['sometimes','integer'],
            'docente_id'     => ['sometimes','integer'],
            'fecha'          => ['sometimes','date'],
            'estado'         => ['sometimes','in:presente,tarde,ausente,permiso'],
            'hora_ingreso'   => ['sometimes','nullable','date_format:H:i'],
            'hora_salida'    => ['sometimes','nullable','date_format:H:i'],
            'programacion_id'=> ['sometimes','nullable','integer'],
            'fuente'         => ['sometimes','in:docente,cpd,sistema'],
            'observacion'    => ['sometimes','nullable','string','max:255'],
        ];
    }
}
