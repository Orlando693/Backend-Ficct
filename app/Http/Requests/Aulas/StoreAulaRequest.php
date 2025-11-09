<?php

namespace App\Http\Requests\Aulas;

use Illuminate\Foundation\Http\FormRequest;

class StoreAulaRequest extends FormRequest
{
    public function authorize(): bool {
        return true; // ya protegemos por middleware
    }

    public function rules(): array {
    return [
        'codigo'      => ['required','string','max:50','unique:aulas,codigo'],
        'tipo'        => ['required','in:TEORIA,LABORATORIO'],
        'capacidad'   => ['required','integer','min:1','max:9999'],
        'edificio_id' => ['nullable','integer'],
    ];
}

    protected function prepareForValidation(): void
    {
        $codigo = $this->input('codigo') ?? $this->input('numero'); // viene del front
        $tipo   = $this->input('tipo');
        if (is_string($tipo)) {
            $tipo = strtoupper($tipo); // 'teoria' -> 'TEORIA'
        }
        $this->merge([
            'codigo' => $codigo,
            'tipo'   => $tipo,
        ]);
    }
}
