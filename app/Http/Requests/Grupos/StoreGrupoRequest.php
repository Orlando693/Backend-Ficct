<?php

namespace App\Http\Requests\Grupos;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrupoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'gestion_id' => ['required','integer','min:1'],
            'materia_id' => ['required','integer','min:1'],
            'paralelo'   => ['required','string','max:10','regex:/^[A-Za-z0-9\-]+$/'],
            'turno'      => ['required','in:manana,tarde,noche'],
            'capacidad'  => ['required','integer','min:1','max:999'],
        ];
    }

    public function messages(): array
    {
        return [
            'gestion_id.required' => 'Selecciona una gestión.',
            'materia_id.required' => 'Selecciona una materia.',
            'paralelo.regex'      => 'Paralelo solo admite letras/números/guion.',
        ];
    }
}
