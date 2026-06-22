<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('building_catalog')) {
            return;
        }

        Schema::table('building_catalog', function (Blueprint $table) {
            if (!Schema::hasColumn('building_catalog', 'yearly_maintenance_json')) {
                $table->json('yearly_maintenance_json')->nullable()->after('yearly_production_json');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('building_catalog')) {
            return;
        }

        Schema::table('building_catalog', function (Blueprint $table) {
            if (Schema::hasColumn('building_catalog', 'yearly_maintenance_json')) {
                $table->dropColumn('yearly_maintenance_json');
            }
        });
    }
};
