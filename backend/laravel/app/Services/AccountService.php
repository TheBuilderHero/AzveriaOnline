<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class AccountService
{
    private const REPLACE_ARRAY_KEYS = [
        'starting_resources',
        'income_resources',
    ];

    public function getNewAccountDefaults(): array
    {
        $defaults = [
            'nation_name_template' => "{name}'s Nation",
            'leader_name_template' => '{name}',
            'alliance_name' => 'Neutral Front',
            'about_text' => 'A rising nation.',
            'default_temp_password' => 'password123',
            'resources' => ['cow' => 100, 'wood' => 100, 'ore' => 100, 'food' => 100],
            'starting_resources' => [
                ['type' => 'base', 'name' => 'cow', 'amount' => 100],
                ['type' => 'base', 'name' => 'wood', 'amount' => 100],
                ['type' => 'base', 'name' => 'ore', 'amount' => 100],
                ['type' => 'base', 'name' => 'food', 'amount' => 100],
            ],
            'income_defaults' => ['cow' => 30, 'wood' => 3, 'ore' => 3, 'food' => 3],
            'income_resources' => [
                ['type' => 'base', 'name' => 'cow', 'amount' => 30],
                ['type' => 'base', 'name' => 'wood', 'amount' => 3],
                ['type' => 'base', 'name' => 'ore', 'amount' => 3],
                ['type' => 'base', 'name' => 'food', 'amount' => 3],
            ],
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
            'terrain_percentages' => ['grassland' => 40, 'mountain' => 20, 'freshwater' => 10, 'hills' => 20, 'desert' => 10, 'seafront' => 0],
            'terrain_square_miles' => ['grassland' => 400, 'mountain' => 200, 'freshwater' => 100, 'hills' => 200, 'desert' => 100, 'seafront' => 0],
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

        return $this->mergeDefaults($defaults, $decoded);
    }

    public function saveNewAccountDefaults(array $overrides): array
    {
        $merged = $this->mergeDefaults($this->getNewAccountDefaults(), $overrides);
        $path = storage_path('app/new_account_defaults.json');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $merged;
    }

    private function mergeDefaults(array $base, array $override): array
    {
        $merged = array_replace_recursive($base, $override);
        foreach (self::REPLACE_ARRAY_KEYS as $key) {
            if (array_key_exists($key, $override) && is_array($override[$key])) {
                $merged[$key] = $override[$key];
            }
        }

        return $merged;
    }

    public function createAccount(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $name = trim((string) ($attributes['name'] ?? ''));
            $email = trim((string) ($attributes['email'] ?? ''));
            $role = (string) ($attributes['role'] ?? 'player');
            $shouldCreateNation = array_key_exists('create_nation', $attributes)
                ? (bool) $attributes['create_nation']
                : $role === 'player';
            $forcePasswordReset = array_key_exists('force_password_reset', $attributes)
                ? (bool) $attributes['force_password_reset']
                : true;

            if ($name === '' || $email === '') {
                throw ValidationException::withMessages([
                    'account' => 'Account name and email are required.',
                ]);
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $attributes['password'],
                'role' => $role,
            ]);

            $this->setForcePasswordReset($user->id, $forcePasswordReset);

            if ($shouldCreateNation) {
                $this->createNationForNewAccount($user->id, $user->name);
            }

            $this->syncGlobalChatMembershipsForUser($user->id);

            return $user->fresh();
        });
    }

    public function createNationForNewAccount(int $userId, string $playerName): int
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
        $startingRows = $this->normalizeDefaultResourceRows($defaults['starting_resources'] ?? []);
        $incomeRows = $this->normalizeDefaultResourceRows($defaults['income_resources'] ?? []);
        $refined = $defaults['refined_resources'] ?? [];
        $currencies = $defaults['currencies'] ?? [];

        // Backward-compatible fallback for old defaults format.
        if (!array_key_exists('starting_resources', $defaults) && empty($startingRows)) {
            foreach (['cow', 'wood', 'ore', 'food'] as $k) {
                if (!array_key_exists($k, $resources)) {
                    continue;
                }
                $startingRows[] = ['type' => 'base', 'name' => $k, 'amount' => (float) $resources[$k]];
            }
            foreach ($refined as $k => $v) {
                $startingRows[] = ['type' => 'advanced', 'name' => (string) $k, 'amount' => (float) $v];
            }
        }

        if (!array_key_exists('income_resources', $defaults) && empty($incomeRows)) {
            $incomeDefaults = $defaults['income_defaults'] ?? ['cow' => 30, 'wood' => 3, 'ore' => 3, 'food' => 3];
            foreach ($incomeDefaults as $k => $v) {
                $incomeRows[] = ['type' => 'base', 'name' => (string) $k, 'amount' => (float) $v];
            }
        }

        $coreBase = [
            'cow' => 0.0,
            'wood' => 0.0,
            'ore' => 0.0,
            'food' => 0.0,
        ];
        $extraBase = [];
        $advanced = [];

        foreach ($startingRows as $row) {
            $type = $row['type'];
            $name = $row['name'];
            $amount = (float) $row['amount'];
            if ($type === 'base') {
                if (array_key_exists($name, $coreBase)) {
                    $coreBase[$name] = $amount;
                } else {
                    $extraBase[$name] = $amount;
                }
            } else {
                $advanced[$name] = $amount;
            }
        }

        $income = [];
        $incomeResources = [];
        foreach ($incomeRows as $row) {
            $type = $row['type'];
            $name = $row['name'];
            $amount = (float) $row['amount'];
            $income[$type . ':' . $name] = $amount;
            $incomeResources[] = ['type' => $type, 'name' => $name, 'amount' => $amount];
        }

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
            'cow' => (float) ($coreBase['cow'] ?? 0),
            'wood' => (float) ($coreBase['wood'] ?? 0),
            'ore' => (float) ($coreBase['ore'] ?? 0),
            'food' => (float) ($coreBase['food'] ?? 0),
            'extra_json' => json_encode([
                'base' => $extraBase,
                'advanced' => $advanced,
                'refined' => $advanced,
                'currencies' => is_array($currencies) ? $currencies : [],
                'income' => $income,
                'income_resources' => $incomeResources,
            ]),
            'updated_at' => now(),
        ]);

        DB::table('nation_terrain_stats')->insert(array_merge(
            ['nation_id' => $nationId],
            $this->buildTerrainStatsPayload($defaults['terrain_square_miles'] ?? []),
            ['updated_at' => now()]
        ));

        return $nationId;
    }

    public function buildTerrainStatsPayload(array $terrainSquareMiles): array
    {
        $read = static function (array $source, array $keys): float {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $source)) {
                    continue;
                }
                $value = (float) $source[$key];
                return max(0, $value);
            }

            return 0;
        };

        $normalized = [
            'grassland' => $read($terrainSquareMiles, ['grassland']),
            'mountain' => $read($terrainSquareMiles, ['mountain']),
            'freshwater' => $read($terrainSquareMiles, ['freshwater']),
            'hills' => $read($terrainSquareMiles, ['hills', 'forest']),
            'desert' => $read($terrainSquareMiles, ['desert']),
            'seafront' => $read($terrainSquareMiles, ['seafront', 'sea_front', 'seaFront']),
        ];

        $extra = [];
        foreach ($terrainSquareMiles as $key => $value) {
            if (array_key_exists($key, $normalized)) {
                continue;
            }
            if (!is_numeric($value)) {
                continue;
            }
            $extra[$key] = max(0, (float) $value);
        }

        $squareMiles = array_merge($normalized, $extra);
        $total = max(1, array_sum($normalized));

        return [
            'grassland_pct' => round(($normalized['grassland'] / $total) * 100, 2),
            'mountain_pct' => round(($normalized['mountain'] / $total) * 100, 2),
            'freshwater_pct' => round(($normalized['freshwater'] / $total) * 100, 2),
            'hills_pct' => round(($normalized['hills'] / $total) * 100, 2),
            'desert_pct' => round(($normalized['desert'] / $total) * 100, 2),
            'seafront_pct' => round(($normalized['seafront'] / $total) * 100, 2),
            'square_miles_json' => json_encode($squareMiles),
        ];
    }

    private function normalizeIntRange(int $a, int $b): array
    {
        $min = min($a, $b);
        $max = max($a, $b);

        return [$min, $max];
    }

    private function normalizeDefaultResourceRows(array $rows): array
    {
        $out = [];
        $seen = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $type = ($row['type'] ?? '') === 'advanced' ? 'advanced' : 'base';
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = $type . ':' . $name;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'type' => $type,
                'name' => $name,
                'amount' => (float) ($row['amount'] ?? 0),
            ];
        }

        return $out;
    }

    public function setForcePasswordReset(int $userId, bool $forcePasswordReset): void
    {
        $settings = DB::table('user_settings')->where('user_id', $userId)->first();
        $extra = json_decode($settings->extra_json ?? '{}', true) ?: [];

        if ($forcePasswordReset) {
            $extra['force_password_reset'] = true;
        } else {
            unset($extra['force_password_reset']);
        }

        DB::table('user_settings')->updateOrInsert(
            ['user_id' => $userId],
            [
                'theme' => $settings?->theme ?? 'light',
                'color_blind_mode' => $settings?->color_blind_mode ?? 'none',
                'dog_bark_enabled' => (int) ($settings?->dog_bark_enabled ?? 0),
                'extra_json' => json_encode($extra),
                'updated_at' => now(),
            ]
        );
    }

    public function syncGlobalChatMembershipsForUser(int $userId): void
    {
        $chatIds = DB::table('chats')->where('type', 'global')->pluck('id');
        foreach ($chatIds as $chatId) {
            DB::table('chat_members')->updateOrInsert(
                ['chat_id' => $chatId, 'user_id' => $userId],
                ['archived_at' => null, 'deleted_at' => null]
            );
        }
    }

    public function syncGlobalChatMembershipsForAllUsers(?int $chatId = null): void
    {
        $userIds = DB::table('users')->pluck('id');
        $chatIds = $chatId !== null
            ? collect([$chatId])
            : DB::table('chats')->where('type', 'global')->pluck('id');

        foreach ($chatIds as $currentChatId) {
            foreach ($userIds as $userId) {
                DB::table('chat_members')->updateOrInsert(
                    ['chat_id' => $currentChatId, 'user_id' => $userId],
                    ['archived_at' => null, 'deleted_at' => null]
                );
            }
        }
    }

    public function deleteAccount(User $user, bool $allowAdmin = false): void
    {
        if (!$allowAdmin && $user->role !== 'player') {
            throw ValidationException::withMessages([
                'user' => 'Only player accounts can be removed from the admin interface.',
            ]);
        }

        DB::transaction(function () use ($user) {
            $nationIds = DB::table('nations')->where('owner_user_id', $user->id)->pluck('id');
            if ($nationIds->isNotEmpty()) {
                DB::table('nations')->whereIn('id', $nationIds)->delete();
            }

            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->delete();

            DB::table('user_settings')->where('user_id', $user->id)->delete();

            $user->delete();
        });
    }
}