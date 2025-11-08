<?php

namespace App\Services;

use App\Models\Aula;

class AulasService
{
    public function crear(array $data): Aula
    {
        // aquí podrías validar colisiones, etc.
        return Aula::create($data);
    }

    public function actualizar(Aula $aula, array $data): Aula
    {
        $aula->update($data);
        return $aula->fresh();
    }

    public function setEstado(Aula $aula, string $estado): Aula
    {
        $aula->estado = $estado;
        $aula->save();
        return $aula;
    }
}
