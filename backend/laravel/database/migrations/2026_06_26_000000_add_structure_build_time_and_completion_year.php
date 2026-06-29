<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('building_catalog')) {
            Schema::table('building_catalog', function (Blueprint $table) {
                if (!Schema::hasColumn('building_catalog', 'build_time_years_json')) {
                    $table->json('build_time_years_json')->nullable()->after('terrain_requirement_json');
                }
            });
        }

        if (Schema::hasTable('nation_buildings')) {
            Schema::table('nation_buildings', function (Blueprint $table) {
                if (!Schema::hasColumn('nation_buildings', 'completes_on_game_year')) {
                    $table->unsignedInteger('completes_on_game_year')->nullable()->after('finishes_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nation_buildings')) {
            Schema::table('nation_buildings', function (Blueprint $table) {
                if (Schema::hasColumn('nation_buildings', 'completes_on_game_year')) {
                    $table->dropColumn('completes_on_game_year');
                }
            });
        }

        if (Schema::hasTable('building_catalog')) {
            Schema::table('building_catalog', function (Blueprint $table) {
                if (Schema::hasColumn('building_catalog', 'build_time_years_json')) {
                    $table->dropColumn('build_time_years_json');
                }
            });
        }
    }
};
