<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('game_document_visibility', function (Blueprint $table) {
            $table->id();
            $table->string('document_code', 80);
            $table->enum('visibility_type', ['admin', 'role', 'all', 'custom'])->default('admin');
            $table->string('role_name')->nullable(); // for role-based
            $table->json('player_ids')->nullable(); // for custom per-player
            $table->timestamps();
            $table->unique(['document_code']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('game_document_visibility');
    }
};
