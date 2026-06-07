<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('unit_catalog')) {
            Schema::table('unit_catalog', function (Blueprint $table) {
                if (!Schema::hasColumn('unit_catalog', 'is_commander')) {
                    $table->boolean('is_commander')->default(false)->after('class_name');
                }
            });
        }

        if (Schema::hasTable('admin_notifications')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('admin_notifications', 'order_status')) {
                    $table->string('order_status', 32)->nullable()->after('type');
                }
                if (!Schema::hasColumn('admin_notifications', 'reviewed_by_user_id')) {
                    $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('read_at');
                    $table->foreign('reviewed_by_user_id')->references('id')->on('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('admin_notifications', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
                }
                if (!Schema::hasColumn('admin_notifications', 'review_note')) {
                    $table->text('review_note')->nullable()->after('reviewed_at');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('admin_notifications')) {
            Schema::table('admin_notifications', function (Blueprint $table) {
                if (Schema::hasColumn('admin_notifications', 'reviewed_by_user_id')) {
                    $table->dropForeign(['reviewed_by_user_id']);
                }
                if (Schema::hasColumn('admin_notifications', 'review_note')) {
                    $table->dropColumn('review_note');
                }
                if (Schema::hasColumn('admin_notifications', 'reviewed_at')) {
                    $table->dropColumn('reviewed_at');
                }
                if (Schema::hasColumn('admin_notifications', 'reviewed_by_user_id')) {
                    $table->dropColumn('reviewed_by_user_id');
                }
                if (Schema::hasColumn('admin_notifications', 'order_status')) {
                    $table->dropColumn('order_status');
                }
            });
        }

        if (Schema::hasTable('unit_catalog')) {
            Schema::table('unit_catalog', function (Blueprint $table) {
                if (Schema::hasColumn('unit_catalog', 'is_commander')) {
                    $table->dropColumn('is_commander');
                }
            });
        }
    }
};
