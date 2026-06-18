<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shop_items') && !Schema::hasColumn('shop_items', 'requirement_json')) {
            Schema::table('shop_items', function (Blueprint $table) {
                $table->json('requirement_json')->nullable()->after('effect_json');
            });
        }

        if (!Schema::hasTable('nation_research')) {
            Schema::create('nation_research', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nation_id')->constrained('nations')->cascadeOnDelete();
                $table->foreignId('shop_item_id')->nullable()->constrained('shop_items')->nullOnDelete();
                $table->string('research_code', 120);
                $table->timestamp('researched_at')->nullable();
                $table->timestamps();

                $table->unique(['nation_id', 'research_code']);
            });
        }

        if (!Schema::hasTable('shop_categories')) {
            return;
        }

        DB::table('shop_categories')->updateOrInsert(['code' => 'craft'], ['display_name' => 'Craft']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'build'], ['display_name' => 'Build']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'recruit'], ['display_name' => 'Recruit']);
        DB::table('shop_categories')->updateOrInsert(['code' => 'research'], ['display_name' => 'Research']);

        $targetIds = [
            'craft' => (int) DB::table('shop_categories')->where('code', 'craft')->value('id'),
            'build' => (int) DB::table('shop_categories')->where('code', 'build')->value('id'),
            'recruit' => (int) DB::table('shop_categories')->where('code', 'recruit')->value('id'),
            'research' => (int) DB::table('shop_categories')->where('code', 'research')->value('id'),
        ];

        $mappings = [
            'craft' => ['refinement', 'crafting', 'currency_exchange'],
            'build' => ['structures', 'upgrades'],
            'recruit' => ['recruitment'],
        ];

        foreach ($mappings as $target => $legacyCodes) {
            $targetId = (int) ($targetIds[$target] ?? 0);
            if ($targetId <= 0) {
                continue;
            }

            foreach ($legacyCodes as $legacyCode) {
                $legacyId = DB::table('shop_categories')->where('code', $legacyCode)->value('id');
                if (!$legacyId) {
                    continue;
                }

                DB::table('shop_items')->where('category_id', (int) $legacyId)->update(['category_id' => $targetId]);
                DB::table('shop_categories')->where('id', (int) $legacyId)->delete();
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shop_items') && Schema::hasColumn('shop_items', 'requirement_json')) {
            Schema::table('shop_items', function (Blueprint $table) {
                $table->dropColumn('requirement_json');
            });
        }

        if (Schema::hasTable('nation_research')) {
            Schema::dropIfExists('nation_research');
        }
    }
};
