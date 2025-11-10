<?php

namespace App\Http\Requests\Parametros;

use Illuminate\Foundation\Http\FormRequest;

class StoreGestionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
          'anio'       => ['required','integer','min:2000','max:2100'],
          'periodo'    => ['required','integer','min:1','max:3'],
          'fecha_ini'  => ['required','date'],
          'fecha_fin'  => ['required','date','after_or_equal:fecha_ini'],
        ];
    }
}
