<?php
namespace App\Http\Requests\Parametros;

use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
          'carrera_id' => ['required','integer','min:1'],
          'materia_id' => ['required','integer','min:1'],
          'plan'       => ['required','integer','min:1900','max:2100'],
          'semestre'   => ['required','integer','min:1','max:12'],
          'tipo'       => ['required','string','max:20'],
          'carga_teo'  => ['required','integer','min:0','max:20'],
          'carga_pra'  => ['required','integer','min:0','max:20'],
        ];
    }
}
