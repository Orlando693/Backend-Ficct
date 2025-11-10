<?php

namespace App\Http\Resources\AsistenciaDocente;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsistenciaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'gestion_id'     => $this->gestion_id,
            'docente_id'     => $this->docente_id,
            'fecha'          => $this->fecha,
            'estado'         => $this->estado,
            'hora_ingreso'   => $this->hora_ingreso,
            'hora_salida'    => $this->hora_salida,
            'programacion_id'=> $this->programacion_id,
            'fuente'         => $this->fuente,
            'observacion'    => $this->observacion,
            'created_at'     => optional($this->created_at)->toDateTimeString(),
            'updated_at'     => optional($this->updated_at)->toDateTimeString(),

            // labels para la UI:
            'docente' => $this->whenLoaded('docente', fn() => [
                'id'   => $this->docente->id_persona ?? $this->docente->id ?? null,
                'name' => trim(($this->docente->nombres ?? '').' '.($this->docente->apellidos ?? '')),
            ]),
            'programacion' => $this->whenLoaded('programacion', fn() => [
                'id'    => $this->programacion->id ?? null,
                'label' => "{$this->programacion->dia} {$this->programacion->hora_ini}-{$this->programacion->hora_fin}",
            ]),
        ];
    }
}
