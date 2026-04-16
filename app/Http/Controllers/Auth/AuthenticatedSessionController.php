<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        // Generate API token and return with user data
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        $team = $user->teams()->first() ?? Team::where('owner_id', $user->id)->first();

        return response()->json([
            'token' => $token,
            'user' => $user,
            'team' => $team,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::logout();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
