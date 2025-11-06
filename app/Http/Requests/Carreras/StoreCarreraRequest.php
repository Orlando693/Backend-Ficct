<?php

namespace App\Http\Requests\Carreras;

use App\Models\Carrera;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCarreraRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['required','string','max:120'],
            'sigla'  => ['required','string','max:15', Rule::unique(Carrera::class,'sigla')],
            'estado' => ['nullable','in:ACTIVA,INACTIVA'],
        ];
    }
}
