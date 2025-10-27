<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // o tu policy
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nombre' => ['sometimes','required','string','max:150'],
            'sigla'  => ['sometimes','required','string','max:10', Rule::unique('carreras','sigla')->ignore($id)],
            'estado' => ['sometimes','required', Rule::in(['ACTIVA','INACTIVA'])],
        ];
    }
}
