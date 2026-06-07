<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('game_time') && !Schema::hasColumn('game_time', 'is_paused')) {
            Schema::table('game_time', function (Blueprint $table) {
                $table->boolean('is_paused')->default(false)->after('auto_increment_enabled');
                $table->timestamp('paused_at')->nullable()->after('is_paused');
            });
        }

        if (!Schema::hasTable('game_time_pause_history')) {
            Schema::create('game_time_pause_history', function (Blueprint $table) {
                $table->id();
                $table->timestamp('paused_at');
                $table->timestamp('resumed_at')->nullable();
                $table->unsignedBigInteger('paused_by_user_id')->nullable();
                $table->unsignedBigInteger('resumed_by_user_id')->nullable();
                $table->text('pause_note')->nullable();
                $table->timestamps();

                $table->foreign('paused_by_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('resumed_by_user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('game_time_pause_history')) {
            Schema::dropIfExists('game_time_pause_history');
        }

        if (Schema::hasTable('game_time')) {
            Schema::table('game_time', function (Blueprint $table) {
                if (Schema::hasColumn('game_time', 'paused_at')) {
                    $table->dropColumn('paused_at');
                }
                if (Schema::hasColumn('game_time', 'is_paused')) {
                    $table->dropColumn('is_paused');
                }
            });
        }
    }
};
