<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'role' => 'user',
            'is_active' => true,
            'timezone' => $request->string('timezone')->toString() ?: 'Africa/Algiers',
        ]);

        $user->load('wallet');
        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => UserResource::make($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->toString())->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account is inactive.'],
            ]);
        }

        $user->load('wallet');
        $token = $user->createToken($request->string('device_name')->toString() ?: 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => UserResource::make($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('wallet');

        return response()->json([
            'user' => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
