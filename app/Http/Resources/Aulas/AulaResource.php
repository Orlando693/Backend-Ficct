<?php

namespace App\Http\Resources\Aulas;

use Illuminate\Http\Resources\Json\JsonResource;

class AulaResource extends JsonResource
{
    public function toArray($request): array
    {
    return [
        'id_aula'  => $this->id,
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
