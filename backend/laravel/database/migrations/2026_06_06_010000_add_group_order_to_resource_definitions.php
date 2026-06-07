<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('resource_definitions') && !Schema::hasColumn('resource_definitions', 'group_order')) {
            Schema::table('resource_definitions', function (Blueprint $table) {
                $table->unsignedInteger('group_order')->default(0)->after('group');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('resource_definitions') && Schema::hasColumn('resource_definitions', 'group_order')) {
            Schema::table('resource_definitions', function (Blueprint $table) {
                $table->dropColumn('group_order');
            });
        }
    }
};
