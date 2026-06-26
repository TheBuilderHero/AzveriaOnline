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
                if (!Schema::hasColumn('building_catalog', 'terrain_requirement_json')) {
                    $table->json('terrain_requirement_json')->nullable()->after('yearly_maintenance_json');
                }
            });
        }

        if (Schema::hasTable('nation_buildings')) {
            Schema::table('nation_buildings', function (Blueprint $table) {
                if (!Schema::hasColumn('nation_buildings', 'terrain_type')) {
                    $table->string('terrain_type', 50)->nullable()->after('status');
                }
                if (!Schema::hasColumn('nation_buildings', 'terrain_allocated_square_miles')) {
                    $table->decimal('terrain_allocated_square_miles', 12, 2)->default(0)->after('terrain_type');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('nation_buildings')) {
            Schema::table('nation_buildings', function (Blueprint $table) {
                if (Schema::hasColumn('nation_buildings', 'terrain_allocated_square_miles')) {
                    $table->dropColumn('terrain_allocated_square_miles');
                }
                if (Schema::hasColumn('nation_buildings', 'terrain_type')) {
                    $table->dropColumn('terrain_type');
                }
            });
        }

        if (Schema::hasTable('building_catalog')) {
            Schema::table('building_catalog', function (Blueprint $table) {
                if (Schema::hasColumn('building_catalog', 'terrain_requirement_json')) {
                    $table->dropColumn('terrain_requirement_json');
                }
            });
        }
    }
};
