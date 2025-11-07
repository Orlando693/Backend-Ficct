<?php

namespace App\Http\Requests\Materias;

use Illuminate\Foundation\Http\FormRequest;

class SetEstadoMateriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'estado' => ['required','in:ACTIVA,INACTIVA'],
        ];
    }
}
