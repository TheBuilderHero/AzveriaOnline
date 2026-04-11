<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'player',
        ]);

        $this->createNationForNewAccount($user->id, $user->name);

        // Auto-add to Global Diplomacy chat
        $globalChat = DB::table('chats')->where('type', 'global')->first();
        if ($globalChat) {
            DB::table('chat_members')->insertOrIgnore([
                'chat_id' => $globalChat->id,
                'user_id' => $user->id,
            ]);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => array_merge($user->toArray(), ['force_password_reset' => false]),
        ], 201);
    }

    public function createNationForNewAccount(int $userId, string $playerName): void
    {
        $defaults = $this->getNewAccountDefaults();

        $nationNameTemplate = (string) ($defaults['nation_name_template'] ?? "{name}'s Nation");
        $nationName = trim(str_replace('{name}', $playerName, $nationNameTemplate));
        if ($nationName === '') {
            $nationName = $playerName . "'s Nation";
        }

        $baseNationName = $nationName;
        $suffix = 2;
        while (DB::table('nations')->where('name', $nationName)->exists()) {
            $nationName = $baseNationName . ' #' . $suffix;
            $suffix++;
        }

        $leaderTemplate = (string) ($defaults['leader_name_template'] ?? '{name}');
        $leaderName = trim(str_replace('{name}', $playerName, $leaderTemplate));
        if ($leaderName === '') {
            $leaderName = $playerName;
        }

        $resources = $defaults['resources'] ?? [];
        $refined = $defaults['refined_resources'] ?? [];
        $currencies = $defaults['currencies'] ?? [];
        $terrainPct = $defaults['terrain_percentages'] ?? [];
        $terrainSq = $defaults['terrain_square_miles'] ?? [];
        $incomeDefaults = $defaults['income_defaults'] ?? ['cow' => 30, 'wood' => 3, 'ore' => 3, 'food' => 3];
        $income = [
            'cow' => (bool) ($defaults['income_randomize_cow'] ?? false)
                ? random_int((int) ($defaults['income_cow_min'] ?? 30), (int) ($defaults['income_cow_max'] ?? 30))
                : (float) ($incomeDefaults['cow'] ?? 30),
            'wood' => (bool) ($defaults['income_randomize_resources'] ?? true)
                ? random_int((int) ($defaults['income_resource_min'] ?? 1), (int) ($defaults['income_resource_max'] ?? 5))
                : (float) ($incomeDefaults['wood'] ?? 3),
            'ore' => (bool) ($defaults['income_randomize_resources'] ?? true)
                ? random_int((int) ($defaults['income_resource_min'] ?? 1), (int) ($defaults['income_resource_max'] ?? 5))
                : (float) ($incomeDefaults['ore'] ?? 3),
            'food' => (bool) ($defaults['income_randomize_resources'] ?? true)
                ? random_int((int) ($defaults['income_resource_min'] ?? 1), (int) ($defaults['income_resource_max'] ?? 5))
                : (float) ($incomeDefaults['food'] ?? 3),
        ];

        $nationId = DB::table('nations')->insertGetId([
            'owner_user_id' => $userId,
            'name' => $nationName,
            'is_placeholder' => 0,
            'leader_name' => $leaderName,
            'alliance_name' => $defaults['alliance_name'] ?? 'Neutral Front',
            'about_text' => $defaults['about_text'] ?? 'A rising nation.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('nation_resources')->insert([
            'nation_id' => $nationId,
            'cow' => (float) ($resources['cow'] ?? 100),
            'wood' => (float) ($resources['wood'] ?? 100),
            'ore' => (float) ($resources['ore'] ?? 100),
            'food' => (float) ($resources['food'] ?? 100),
            'extra_json' => json_encode([
                'refined' => is_array($refined) ? $refined : [],
                'currencies' => is_array($currencies) ? $currencies : [],
                'income' => $income,
            ]),
            'updated_at' => now(),
        ]);

        DB::table('nation_terrain_stats')->insert([
            'nation_id' => $nationId,
            'grassland_pct' => (float) ($terrainPct['grassland'] ?? 40),
            'mountain_pct' => (float) ($terrainPct['mountain'] ?? 20),
            'freshwater_pct' => (float) ($terrainPct['freshwater'] ?? 10),
            'hills_pct' => (float) ($terrainPct['hills'] ?? 20),
            'desert_pct' => (float) ($terrainPct['desert'] ?? 10),
            'square_miles_json' => json_encode([
                'grassland' => (float) ($terrainSq['grassland'] ?? 400),
                'mountain' => (float) ($terrainSq['mountain'] ?? 200),
                'freshwater' => (float) ($terrainSq['freshwater'] ?? 100),
                'hills' => (float) ($terrainSq['hills'] ?? 200),
                'desert' => (float) ($terrainSq['desert'] ?? 100),
            ]),
            'updated_at' => now(),
        ]);
    }

    private function getNewAccountDefaults(): array
    {
        $defaults = [
            'nation_name_template' => "{name}'s Nation",
            'leader_name_template' => '{name}',
            'alliance_name' => 'Neutral Front',
            'about_text' => 'A rising nation.',
            'default_temp_password' => 'password123',
            'resources' => ['cow' => 100, 'wood' => 100, 'ore' => 100, 'food' => 100],
            'income_defaults' => ['cow' => 30, 'wood' => 3, 'ore' => 3, 'food' => 3],
            'income_randomize_resources' => true,
            'income_resource_min' => 1,
            'income_resource_max' => 5,
            'income_randomize_cow' => false,
            'income_cow_min' => 30,
            'income_cow_max' => 30,
            'refined_resources' => [
                'M' => 0, 'RM' => 0, 'FS' => 0, 'URM' => 0, 'AD' => 0, 'AM' => 0, 'DM' => 0, 'DE' => 0,
                'H' => 0, 'TW' => 0, 'CB' => 0, 'MYC' => 0, 'SM' => 0, 'CFB' => 0, 'BST' => 0, 'CGM' => 0,
                'GBR' => 0, 'CHB' => 0, 'SR' => 0, 'ZZ' => 0, 'PZA' => 0, 'IC' => 0, 'WSH' => 0, 'SD' => 0, 'NS' => 0,
                'K' => 0, 'RK' => 0, 'DP' => 0,
            ],
            'currencies' => ['GB' => 0, 'P' => 0, 'G' => 0, 'S' => 0, 'B' => 0, 'X' => 0, 'CD' => 0, 'FD' => 0, 'cheese' => 0, 'SP' => 0, 'R' => 0, 'MK' => 0],
            'terrain_percentages' => ['grassland' => 40, 'mountain' => 20, 'freshwater' => 10, 'hills' => 20, 'desert' => 10],
            'terrain_square_miles' => ['grassland' => 400, 'mountain' => 200, 'freshwater' => 100, 'hills' => 200, 'desert' => 100],
        ];

        $path = storage_path('app/new_account_defaults.json');
        if (!is_file($path)) {
            return $defaults;
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $decoded);
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

        $settings = DB::table('user_settings')->where('user_id', $user->id)->first();
        $extra = json_decode($settings->extra_json ?? '{}', true) ?: [];
        unset($extra['force_password_reset']);
        DB::table('user_settings')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'theme' => $settings?->theme ?? 'light',
                'color_blind_mode' => $settings?->color_blind_mode ?? 'none',
                'dog_bark_enabled' => (int) ($settings?->dog_bark_enabled ?? 0),
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]
        );

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

        $settings = DB::table('user_settings')->where('user_id', $user->id)->first();
        $extra = json_decode($settings->extra_json ?? '{}', true) ?: [];
        $extra['force_password_reset'] = (bool) ($data['force_password_reset'] ?? true);
        DB::table('user_settings')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'theme' => $settings?->theme ?? 'light',
                'color_blind_mode' => $settings?->color_blind_mode ?? 'none',
                'dog_bark_enabled' => (int) ($settings?->dog_bark_enabled ?? 0),
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]
        );

        return response()->json(['message' => 'Password reset']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
