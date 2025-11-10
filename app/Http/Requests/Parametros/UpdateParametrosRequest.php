<?php

namespace App\Http\Requests\Parametros;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParametrosRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
          'duracion_bloque_min' => ['required','integer','min:5','max:300'],
          'dias_habiles' => ['required','array','min:1'],
          'dias_habiles.*' => ['integer','min:1','max:7'],
          'turnos' => ['required','array','min:1'],
          'turnos.*.turno'  => ['required','in:manana,tarde,noche'],
          'turnos.*.inicio' => ['required','regex:/^\d{2}:\d{2}$/'],
          'turnos.*.fin'    => ['required','regex:/^\d{2}:\d{2}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // normaliza strings
        if ($this->has('turnos')) {
            $t = array_map(function($x){
                $x['turno']  = strtolower($x['turno'] ?? '');
                return $x;
            }, $this->input('turnos', []));
            $this->merge(['turnos' => $t]);
        }
    }
}
