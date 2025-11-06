<?php

namespace App\Http\Requests\Carreras;

use App\Models\Carrera;
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
                Rule::unique(Carrera::class,'sigla')->ignore($id),
            ],
            'estado' => ['sometimes','required','in:ACTIVA,INACTIVA'],
        ];
    }
}
