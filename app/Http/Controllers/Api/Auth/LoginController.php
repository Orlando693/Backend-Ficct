<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string','min:6'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales inválidas.'],
            ]);
        }

        if (in_array($user->status, ['BLOQUEADO','INACTIVO'])) {
            return response()->json(['message' => 'Cuenta no habilitada.'], 403);
        }

        // abilities según rol (puedes ampliar)
        $abilities = match ($user->role) {
            'CPD','Decanato' => ['users.manage','*'],
            'Jefatura'       => ['*'],
            default          => ['read'],
        };

        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->toFrontend(),
            'abilities' => $abilities,
        ]);
    }
}
