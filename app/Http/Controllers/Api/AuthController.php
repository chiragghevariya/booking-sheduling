<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** POST /api/auth/register — create a customer account and start a session. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? User::ROLE_CUSTOMER,
            'timezone' => $data['timezone'] ?? 'UTC',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'data' => new UserResource($user),
        ], 201);
    }

    /** POST /api/auth/login — cookie-based SPA login via the web guard. */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'data' => new UserResource($request->user()),
        ]);
    }

    /** POST /api/auth/logout — invalidate the SPA session. */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    /** GET /api/auth/me — current authenticated user. */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    // ------------------------------------------------------------------
    // Token-mode endpoints for the mobile (Expo) app.
    //
    // These run alongside the cookie-mode methods above. The mobile app
    // sends no cookies; instead it stores the plainTextToken returned
    // here and sends it back on every request as:
    //     Authorization: Bearer <token>
    // The auth:sanctum guard recognizes the token automatically.
    // ------------------------------------------------------------------

    /** POST /api/register — register a customer and return a personal access token. */
    public function tokenRegister(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_CUSTOMER,
            'timezone' => $data['timezone'] ?? 'UTC',
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /** POST /api/login — verify credentials and return a personal access token. */
    public function tokenLogin(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Validate the credentials WITHOUT starting a session — mobile is stateless.
        if (! Auth::guard('web')->validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var User $user */
        $user = User::where('email', $credentials['email'])->firstOrFail();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'data' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /** GET /api/user — return the user identified by the Bearer token. */
    public function tokenUser(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * POST /api/logout — revoke ONLY the current token.
     * Any other tokens (e.g. a second device) keep working.
     */
    public function tokenLogout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
