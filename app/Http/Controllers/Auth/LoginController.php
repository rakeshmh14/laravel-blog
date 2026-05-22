<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginController extends Controller
{
    /**
     * Login
     *
     * Authenticate with email and password. Returns a Sanctum bearer token.
     */
    public function store(LoginRequest $request): LoginResponse
    {
        $request->authenticate();

        $user = $request->user();
        $token = $user->createToken('main')->plainTextToken;

        return new LoginResponse($user, $token);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        return response()->noContent();
    }
}
