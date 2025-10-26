<?php

namespace App\Http\Controllers\Api\Usuarios;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    // GET /api/users?q=&rol=&estado=&page=
    public function index(Request $request)
    {
        $q      = $request->query('q');
        $rol    = $request->query('rol');
        $estado = $request->query('estado');

        $users = User::query()
            ->when($q, function ($qb) use ($q) {
                $like = '%' . str_replace('%', '\%', $q) . '%';
                $qb->where(function ($qq) use ($like) {
                    $qq->whereRaw('name ILIKE ?', [$like])
                       ->orWhereRaw('email ILIKE ?', [$like])
                       ->orWhereRaw('username ILIKE ?', [$like]);
                });
            })
            ->when($rol, fn ($qb) => $qb->where('role', $rol))
            ->when($estado, fn ($qb) => $qb->where('status', $estado))
            ->latest('id')
            ->paginate(10);

        return response()->json([
            'data' => $users->getCollection()->map->toFrontend()->values(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    // POST /api/users
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre'   => ['required','string','max:150'],
            'correo'   => ['required','email','max:150','unique:users,email'],
            'username' => ['nullable','string','max:40','unique:users,username'],
            'telefono' => ['nullable','string','max:30'],
            'rol'      => ['required', Rule::in(['Decanato','CPD','Jefatura','Docente'])],
            'estado'   => ['nullable', Rule::in(['ACTIVO','BLOQUEADO','PENDIENTE','INACTIVO'])],
            'password' => ['nullable','string','min:6'],
        ]);

        // Si no enviaste password, el sistema genera una temporal
        $plain = $data['password'] ?? Str::random(10);

        $user = User::create([
            'name'     => $data['nombre'],
            'email'    => $data['correo'],
            'username' => $data['username'] ?? null,
            'phone'    => $data['telefono'] ?? null,
            'role'     => $data['rol'],
            'status'   => $data['estado'] ?? 'PENDIENTE',
            'password' => Hash::make($plain),
            'must_change_password' => true, // obliga a cambiarla al primer login
        ]);

        $resp = $user->toFrontend();
        // devolvemos la contraseña generada/establecida para que el CPD pueda copiarla
        $resp['temp_password'] = $plain;

        return response()->json($resp, 201);
    }

    // PUT /api/users/{id}
    public function update($id, Request $request)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'nombre'   => ['required','string','max:150'],
            'correo'   => ['required','email','max:150', Rule::unique('users','email')->ignore($user->id)],
            'username' => ['nullable','string','max:40', Rule::unique('users','username')->ignore($user->id)],
            'telefono' => ['nullable','string','max:30'],
            'rol'      => ['required', Rule::in(['Decanato','CPD','Jefatura','Docente'])],
            'estado'   => ['required', Rule::in(['ACTIVO','BLOQUEADO','PENDIENTE','INACTIVO'])],
            'password' => ['nullable','string','min:6'],
        ]);

        $user->fill([
            'name'     => $data['nombre'],
            'email'    => $data['correo'],
            'username' => $data['username'] ?? null,
            'phone'    => $data['telefono'] ?? null,
            'role'     => $data['rol'],
            'status'   => $data['estado'],
        ]);

        $respPassword = null;
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
            $user->must_change_password = true;
            $respPassword = $data['password'];
        }

        $user->save();

        $resp = $user->toFrontend();
        if ($respPassword) {
            $resp['temp_password'] = $respPassword;
        }

        return response()->json($resp);
    }

    // PATCH /api/users/{id}/role
    public function changeRole($id, Request $request)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'rol' => ['required', Rule::in(['Decanato','CPD','Jefatura','Docente'])],
        ]);

        $user->role = $data['rol'];
        $user->save();

        return response()->json($user->toFrontend());
    }

    // PATCH /api/users/{id}/toggle-block
    public function toggleBlock($id)
    {
        $user = User::findOrFail($id);

        $user->status = $user->status === 'BLOQUEADO' ? 'ACTIVO' : 'BLOQUEADO';
        $user->save();

        return response()->json($user->toFrontend());
    }

    // DELETE /api/users/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['ok' => true]);
    }
}
