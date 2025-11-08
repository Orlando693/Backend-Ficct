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
            'codigo'      => ['required','string','max:50', Rule::unique('aulas','codigo')->ignore($id)],
            'tipo'        => ['required','in:TEORIA,LABORATORIO'],
            'capacidad'   => ['required','integer','min:1','max:9999'],
            'edificio_id' => ['nullable','integer'],
        ];
    }
}
