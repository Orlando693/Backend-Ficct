<?php

namespace App\Http\Resources\Aulas;

use Illuminate\Http\Resources\Json\JsonResource;

class AulaResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'codigo'        => $this->codigo,
            'tipo'          => $this->tipo,
            'capacidad'     => $this->capacidad,
            'edificio_id'   => $this->edificio_id,
            'edificio_label'=> optional($this->edificio)->nombre ?? null,
            'estado'        => $this->estado,
            'created_at'    => optional($this->created_at)->toISOString(),
            'updated_at'    => optional($this->updated_at)->toISOString(),
        ];
    }
}
