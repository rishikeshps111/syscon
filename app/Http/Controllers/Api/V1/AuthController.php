<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const USER_RELATIONS = [
        'roles',
        'driverProfile.depot',
        'controllerProfile.depot',
        'supervisorProfile.depot',
        'driverDocuments.documentType',
        'controllerDocuments.documentType',
        'supervisorDocuments.documentType',
    ];

    private const ALLOWED_ROLES = [
        'driver' => 'Driver',
        'controller' => 'Controller',
        'supervisor' => 'Supervisor',
    ];

    private const MAX_FAILED_LOGIN_ATTEMPTS = 3;

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $type = $data['type'];

        $user = User::query()
            ->role(self::ALLOWED_ROLES[$type])
            ->where('code', $data['phone'])
            ->with(self::USER_RELATIONS)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! Hash::check($data['passcode'], $user->password)) {
            $failedAttempts = (int) $user->failed_login_attempts + 1;

            $user->forceFill([
                'failed_login_attempts' => min($failedAttempts, self::MAX_FAILED_LOGIN_ATTEMPTS),
                'is_active' => $failedAttempts >= self::MAX_FAILED_LOGIN_ATTEMPTS ? false : $user->is_active,
            ])->save();

            if ($failedAttempts >= self::MAX_FAILED_LOGIN_ATTEMPTS) {
                throw ValidationException::withMessages([
                    'passcode' => ['3 attempts already done with wrong passcode, so your account has been blocked.'],
                ]);
            }

            throw ValidationException::withMessages([
                'passcode' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'phone' => ['This user account is inactive.'],
            ]);
        }

        if ($user->failed_login_attempts > 0) {
            $user->forceFill(['failed_login_attempts' => 0])->save();
        }

        if (! $user->hasRole(self::ALLOWED_ROLES[$type])) {
            throw ValidationException::withMessages([
                'type' => ['The selected user type is not valid for this user.'],
            ]);
        }

        $user->setAttribute('api_user_type', $type);

        $token = $user->createToken(
            $data['device_name'] ?? ucfirst($type) . ' App',
            ['type:' . $type]
        )->plainTextToken;

        if (! empty($data['fcm_token'])) {
            $this->storeDeviceToken($user, $data['fcm_token'], $data['platform'] ?? null);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(self::USER_RELATIONS);
        $user->setAttribute('api_user_type', $this->userTypeFor($user));

        return response()->json([
            'success' => true,
            'message' => 'Authenticated user fetched successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->filled('fcm_token')) {
            $request->user()->deviceTokens()
                ->where('token_hash', hash('sha256', (string) $request->input('fcm_token')))
                ->delete();
        }

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    public function updateDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        $this->storeDeviceToken($request->user(), $data['fcm_token'], $data['platform'] ?? null);

        return response()->json(['success' => true, 'message' => 'Device token saved successfully.']);
    }

    private function storeDeviceToken(User $user, string $token, ?string $platform): void
    {
        UserDeviceToken::updateOrCreate(
            ['token_hash' => hash('sha256', $token)],
            ['user_id' => $user->id, 'token' => $token, 'platform' => $platform, 'last_used_at' => now()]
        );
    }

    private function userTypeFor(User $user): ?string
    {
        foreach (self::ALLOWED_ROLES as $type => $role) {
            if ($user->hasRole($role)) {
                return $type;
            }
        }

        return null;
    }
}
