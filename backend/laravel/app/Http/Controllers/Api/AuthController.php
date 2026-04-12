<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private AccountService $accounts)
    {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:3', 'max:120', "regex:/^[A-Za-z0-9][A-Za-z0-9 _'\\-]*$/"],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
        ], [
            'name.required' => 'Enter a display name before creating the account.',
            'name.min' => 'Display names must be at least 3 characters long.',
            'name.max' => 'Display names cannot be longer than 120 characters.',
            'name.regex' => 'Display names may use letters, numbers, spaces, apostrophes, hyphens, and underscores only.',
            'email.required' => 'Enter an email address for the new account.',
            'email.email' => 'Enter a valid email address, such as leader@example.com.',
            'email.unique' => 'That email address is already in use. Sign in instead or choose another one.',
            'password.required' => 'Enter a password for the account.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $name = trim((string) $request->input('name'));
            if ($name !== '' && preg_match('/\s{2,}/', $name)) {
                $validator->errors()->add('name', 'Display names cannot contain repeated spaces.');
            }
        });

        $data = $validator->validate();

        $user = $this->accounts->createAccount([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'player',
            'create_nation' => true,
            'force_password_reset' => false,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => array_merge($user->toArray(), ['force_password_reset' => false]),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        $token = $user->createToken('api')->plainTextToken;
        $settings = DB::table('user_settings')->where('user_id', $user->id)->first();
        $extra = json_decode($settings->extra_json ?? '{}', true) ?: [];
        $forcePasswordReset = (bool) ($extra['force_password_reset'] ?? false);

        return response()->json([
            'token' => $token,
            'user' => array_merge($user->toArray(), ['force_password_reset' => $forcePasswordReset]),
        ]);
    }

    public function changeOwnPassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', Password::min(8)],
        ]);

        $user = $request->user();
        if (!$user || !Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->password = $data['new_password'];
        $user->save();

        $this->accounts->setForcePasswordReset($user->id, false);

        return response()->json(['message' => 'Password updated']);
    }

    public function adminResetPassword(Request $request, int $userId)
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $data = $request->validate([
            'new_password' => ['required', Password::min(8)],
            'force_password_reset' => ['sometimes', 'boolean'],
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->password = $data['new_password'];
        $user->save();

        $this->accounts->setForcePasswordReset($user->id, (bool) ($data['force_password_reset'] ?? true));

        return response()->json(['message' => 'Password reset']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
