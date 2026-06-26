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
            if (!Schema::hasColumn('building_catalog', 'list_order')) {
                $table->unsignedInteger('list_order')->default(0)->after('max_level');
            }

            if (!Schema::hasColumn('building_catalog', 'yearly_production_json')) {
                $table->json('yearly_production_json')->nullable()->after('list_order');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('building_catalog')) {
            return;
        }

        Schema::table('building_catalog', function (Blueprint $table) {
            if (Schema::hasColumn('building_catalog', 'yearly_production_json')) {
                $table->dropColumn('yearly_production_json');
            }

            if (Schema::hasColumn('building_catalog', 'list_order')) {
                $table->dropColumn('list_order');
            }
        });
    }
};
