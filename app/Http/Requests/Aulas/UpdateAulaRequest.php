<?php

namespace App\Http\Requests\Aulas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAulaRequest extends FormRequest
{
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        $id = $this->route('id');
        return [
            'codigo'      => ['required','string','max:50', Rule::unique('academia.aula','codigo')->ignore($id, 'id_aula')],
            'tipo'        => ['required','in:teoria,laboratorio,auditorio,otros'],
            'capacidad'   => ['required','integer','min:1','max:9999'],
            'edificio_id' => ['nullable','integer'],
        ];
    }

protected function prepareForValidation(): void
{
    $codigo = $this->input('codigo') ?? $this->input('numero');
    $tipo   = $this->input('tipo');
    if (is_string($tipo)) {
        $tipo = strtolower($tipo);
    }
    $this->merge([
        'codigo' => $codigo,
        'tipo'   => $tipo,
    ]);
}
}
