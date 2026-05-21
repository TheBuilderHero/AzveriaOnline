<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillCanonicalResourceKeys extends Command
{
    protected $signature = 'azveria:resources:backfill-canonical {--dry-run : Show changes without writing}';

    protected $description = 'Backfill legacy resource keys (ref_/cur_/refined) into canonical dynamic keys';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $nationUpdates = $this->backfillNationResources($dryRun);
        $shopUpdates = $this->backfillShopItems($dryRun);

        $this->info('Nation rows updated: ' . $nationUpdates);
        $this->info('Shop items updated: ' . $shopUpdates);
        if ($dryRun) {
            $this->warn('Dry run only. No rows were written.');
        }

        return self::SUCCESS;
    }

    private function backfillNationResources(bool $dryRun): int
    {
        $rows = DB::table('nation_resources')->select('nation_id', 'extra_json')->get();
        $updated = 0;

        foreach ($rows as $row) {
            $extra = json_decode((string) ($row->extra_json ?? '{}'), true);
            if (!is_array($extra)) {
                $extra = [];
            }

            $hasRefined = is_array($extra['refined'] ?? null);
            $hasIncomeRows = is_array($extra['income_resources'] ?? null);
            $income = is_array($extra['income'] ?? null) ? $extra['income'] : [];
            $hasLegacyIncomeKeys = false;
            foreach ($income as $rawKey => $_value) {
                if (!$this->isCanonicalIncomeKey((string) $rawKey)) {
                    $hasLegacyIncomeKeys = true;
                    break;
                }
            }

            if (!$hasRefined && !$hasLegacyIncomeKeys && $hasIncomeRows) {
                continue;
            }

            $advanced = is_array($extra['advanced'] ?? null) ? $extra['advanced'] : [];
            $refined = is_array($extra['refined'] ?? null) ? $extra['refined'] : [];
            foreach ($refined as $name => $amount) {
                $resourceName = trim((string) $name);
                if ($resourceName === '' || array_key_exists($resourceName, $advanced)) {
                    continue;
                }
                $advanced[$resourceName] = (float) $amount;
            }
            $extra['advanced'] = $advanced;
            unset($extra['refined']);

            $normalizedIncome = [];
            foreach ($income as $rawKey => $value) {
                $canonicalKey = $this->canonicalizeResourceKey((string) $rawKey);
                if ($canonicalKey === null) {
                    continue;
                }
                $normalizedIncome[$canonicalKey] = (float) ($normalizedIncome[$canonicalKey] ?? 0) + (float) $value;
            }
            $extra['income'] = $normalizedIncome;

            $incomeRows = [];
            foreach ($normalizedIncome as $key => $amount) {
                [$type, $name] = explode(':', $key, 2);
                $incomeRows[] = [
                    'type' => $type,
                    'name' => $name,
                    'amount' => (float) $amount,
                ];
            }
            $extra['income_resources'] = $incomeRows;

            $encoded = json_encode($extra);
            if ($encoded === false) {
                continue;
            }

            $updated++;
            if (!$dryRun) {
                DB::table('nation_resources')
                    ->where('nation_id', $row->nation_id)
                    ->update([
                        'extra_json' => $encoded,
                        'updated_at' => now(),
                    ]);
            }
        }

        return $updated;
    }

    private function backfillShopItems(bool $dryRun): int
    {
        $rows = DB::table('shop_items')->select('id', 'cost_json', 'maintenance_json', 'yearly_effect_json')->get();
        $updated = 0;

        foreach ($rows as $row) {
            $changes = [];

            foreach (['cost_json', 'maintenance_json', 'yearly_effect_json'] as $column) {
                $raw = $row->{$column};
                if ($raw === null || $raw === '') {
                    continue;
                }

                $decoded = json_decode((string) $raw, true);
                if (!is_array($decoded)) {
                    continue;
                }

                $normalized = [];
                foreach ($decoded as $rawKey => $amount) {
                    $canonicalKey = $this->canonicalizeResourceKey((string) $rawKey);
                    if ($canonicalKey === null) {
                        continue;
                    }
                    $normalized[$canonicalKey] = (float) ($normalized[$canonicalKey] ?? 0) + (float) $amount;
                }

                $encoded = json_encode($normalized);
                if ($encoded !== false && $encoded !== json_encode($decoded)) {
                    $changes[$column] = $encoded;
                }
            }

            if (empty($changes)) {
                continue;
            }

            $updated++;
            if (!$dryRun) {
                DB::table('shop_items')->where('id', $row->id)->update($changes);
            }
        }

        return $updated;
    }

    private function canonicalizeResourceKey(string $rawKey): ?string
    {
        $key = trim($rawKey);
        if ($key === '') {
            return null;
        }

        if (str_contains($key, ':')) {
            [$rawType, $rawName] = explode(':', $key, 2);
            $type = strtolower(trim($rawType));
            $name = trim($rawName);
            if ($name === '') {
                return null;
            }

            if ($type === 'base') {
                return 'base:' . $name;
            }
            if ($type === 'advanced' || $type === 'refined') {
                return 'advanced:' . $name;
            }
            if ($type === 'currency' || $type === 'curr' || $type === 'currencies') {
                return 'currencies:' . $name;
            }

            return null;
        }

        if (str_starts_with($key, 'ref_')) {
            $name = substr($key, 4);
            return $name === '' ? null : 'advanced:' . $name;
        }

        if (str_starts_with($key, 'cur_')) {
            $name = substr($key, 4);
            return $name === '' ? null : 'currencies:' . $name;
        }

        return 'base:' . $key;
    }

    private function isCanonicalIncomeKey(string $rawKey): bool
    {
        $key = trim($rawKey);
        if ($key === '') {
            return false;
        }

        if (!str_contains($key, ':')) {
            return false;
        }

        [$rawType, $rawName] = explode(':', $key, 2);
        $type = strtolower(trim($rawType));
        $name = trim($rawName);

        return ($type === 'base' || $type === 'advanced') && $name !== '';
    }
}
