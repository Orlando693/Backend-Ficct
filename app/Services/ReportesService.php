<?php

namespace App\Services;

class ReportesService
{
    public function generar(array $filtros): array
    {
        $tipo = $filtros['tipo']; // 'horarios' | 'carga' | 'asistencia' | 'aulas'
        $n = 14 + random_int(0, 7);

        $carreras = ["Sistemas", "Informática", "Industrial"];
        $materias = ["BD I", "Algoritmos", "Ingeniería de SW", "Redes"];
        $grupos   = ["A-1", "A-2", "B-1", "B-2"];
        $aulas    = ["A-101", "A-102", "B-201", "B-202"];
        $dias     = ["Lun","Mar","Mié","Jue","Vie"];

        $docenteFijo = $filtros['docente'] ?? null;
        $materiaFija = $filtros['materia'] ?? null;
        $grupoFijo   = $filtros['grupo']   ?? null;
        $aulaFija    = $filtros['aula']    ?? null;

        $rows = [];

        for ($i = 0; $i < $n; $i++) {
            if ($tipo === 'horarios') {
                $rows[] = [
                    'kind'    => 'horario',
                    'docente' => $docenteFijo ?: $this->pick($this->docentesDemo()),
                    'materia' => $materiaFija ?: $this->pick($materias),
                    'grupo'   => $grupoFijo   ?: $this->pick($grupos),
                    'aula'    => $aulaFija    ?: $this->pick($aulas),
                    'dia'     => $this->pick($dias),
                    'hi'      => $this->pick(["08:15","10:15","14:15","16:15","18:30"]),
                    'hf'      => $this->pick(["10:00","12:00","16:00","18:00","20:15"]),
                ];
            } elseif ($tipo === 'carga') {
                $rows[] = [
                    'kind'    => 'carga',
                    'docente' => $docenteFijo ?: $this->pick($this->docentesDemo()),
                    'carrera' => $this->pick($carreras),
                    'horas'   => random_int(4, 24),
                ];
            } elseif ($tipo === 'asistencia') {
                $total = random_int(20, 48);
                $pres  = random_int((int)round($total * 0.55), $total);
                $rows[] = [
                    'kind'     => 'asistencia',
                    'docente'  => $docenteFijo ?: $this->pick($this->docentesDemo()),
                    'grupo'    => $grupoFijo ?: $this->pick($grupos),
                    'fecha'    => date('Y-m-d', strtotime("-".random_int(0, 30)." days")),
                    'presentes'=> $pres,
                    'total'    => $total,
                ];
            } else { // aulas
                $rows[] = [
                    'kind'   => 'aula',
                    'aula'   => $aulaFija ?: $this->pick($aulas),
                    'dia'    => $this->pick($dias),
                    'bloque' => $this->pick(["08:15-10:00","10:15-12:00","14:15-16:00","16:15-18:00","18:30-20:15"]),
                    'estado' => (mt_rand() / mt_getrandmax()) > 0.45 ? 'OCUPADA' : 'DISPONIBLE',
                ];
            }
        }

        return $rows;
    }

    private function pick(array $arr)
    {
        return $arr[array_rand($arr)];
    }

    private function docentesDemo(): array
    {
        return ["Juan Pérez", "María Gómez", "Luis Rojas", "Ana Torres"];
    }
}
