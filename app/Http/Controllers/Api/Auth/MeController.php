<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'user' => $u->toFrontend(),
        ]);
    }
}
