<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;

class StoreCarreraRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre' => ['required','string','max:120'],
            'sigla'  => ['required','string','max:15','unique:academia.carreras,sigla'],
            'codigo' => ['required','string','max:30','unique:academia.carreras,codigo'],
            'estado' => ['nullable','in:ACTIVA,INACTIVA'],
        ];
    }
}
