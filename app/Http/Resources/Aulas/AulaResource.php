<?php

namespace App\Http\Resources\Aulas;

use Illuminate\Http\Resources\Json\JsonResource;

class AulaResource extends JsonResource
{
    public function toArray($request): array
    {
        $id = $this->id_aula ?? $this->getAttribute('id') ?? $this->getKey();

        return [
            'id_aula'  => (int) $id,
            'numero'   => $this->codigo,
            'tipo'     => is_string($this->tipo) ? strtolower($this->tipo) : $this->tipo,
            'capacidad'=> $this->capacidad,
            'piso'     => null, // no mapeas piso en DB; el front lo muestra como '-'
            'estado'   => match (strtolower((string)$this->estado)) {
            'activa'   => 'activo',
            'inactiva' => 'inactivo',
            default    => 'inactivo',
        },
    ];
}

}
