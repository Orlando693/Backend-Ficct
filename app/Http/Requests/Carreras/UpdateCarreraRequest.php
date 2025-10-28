<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarreraRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('id'); // /carreras/{id}
        return [
            'nombre' => ['sometimes','required','string','max:120'],
            'sigla'  => [
                'sometimes','required','string','max:15',
                Rule::unique('academia.carreras','sigla')->ignore($id, 'id_carrera'),
            ],
            'codigo' => [
                'sometimes','required','string','max:30',
                Rule::unique('academia.carreras','codigo')->ignore($id, 'id_carrera'),
            ],
            'estado' => ['sometimes','required','in:ACTIVA,INACTIVA'],
        ];
    }
}
