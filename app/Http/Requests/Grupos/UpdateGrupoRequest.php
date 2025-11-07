<?php

namespace App\Http\Requests\Grupos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGrupoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'paralelo'   => ['sometimes','string','max:10','regex:/^[A-Za-z0-9\-]+$/'],
            'turno'      => ['sometimes','in:manana,tarde,noche'],
            'capacidad'  => ['sometimes','integer','min:1','max:999'],
            'estado'     => ['sometimes','in:ACTIVO,INACTIVO'],
        ];
    }
}
