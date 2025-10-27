<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o tu policy
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required','string','max:150'],
            'sigla'  => ['required','string','max:10', Rule::unique('carreras','sigla')],
            'estado' => ['nullable', Rule::in(['ACTIVA','INACTIVA'])],
        ];
    }
}
