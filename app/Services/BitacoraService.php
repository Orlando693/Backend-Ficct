<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Http\Request;

class BitacoraService
{
    public function record(array $data = [], ?Request $request = null): Bitacora
    {
        $req = $request ?: request();

        $usuario = null;
        if ($req && $req->user()) {
            $u = $req->user();
            $usuario = $u->name ?? $u->username ?? $u->email ?? null;
        } else {
            $usuario = $data['usuario'] ?? null;
        }

        return Bitacora::create([
            'modulo'      => $data['modulo']      ?? 'General',
            'accion'      => $data['accion']      ?? 'DESCONOCIDA',
            'descripcion' => $data['descripcion'] ?? null,
            'usuario'     => $usuario,
            'ip'          => $req?->ip() ?? $data['ip'] ?? null,
            'created_at'  => now(),
        ]);
    }
}
