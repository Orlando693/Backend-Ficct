<?php

namespace App\Http\Requests\Materias;

use Illuminate\Foundation\Http\FormRequest;

class StoreMateriaRequest extends FormRequest
{
    public function authorize(): bool { return true; } // ajusta a tu policy/roles
    public function rules(): array
    {
        return [
            'codigo'   => ['required','string','max:30'],
            'nombre'   => ['required','string','max:120'],
            'creditos' => ['required','integer','min:0','max:20'],
        ];
    }
}
