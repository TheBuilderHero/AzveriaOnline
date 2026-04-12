<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->unique();
            $table->boolean('is_placeholder')->default(false);
            $table->string('leader_name')->nullable();
            $table->string('alliance_name')->nullable();
            $table->text('about_text')->nullable();
            $table->timestamps();
        });

        Schema::create('nation_resources', function (Blueprint $table) {
            $table->foreignId('nation_id')->primary()->constrained('nations')->cascadeOnDelete();
            $table->decimal('cow', 14, 2)->default(0);
            $table->decimal('wood', 14, 2)->default(0);
            $table->decimal('ore', 14, 2)->default(0);
            $table->decimal('food', 14, 2)->default(0);
            $table->json('extra_json')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('nation_terrain_stats', function (Blueprint $table) {
            $table->foreignId('nation_id')->primary()->constrained('nations')->cascadeOnDelete();
            $table->decimal('grassland_pct', 5, 2)->default(0);
            $table->decimal('mountain_pct', 5, 2)->default(0);
            $table->decimal('freshwater_pct', 5, 2)->default(0);
            $table->decimal('hills_pct', 5, 2)->default(0);
            $table->decimal('desert_pct', 5, 2)->default(0);
            $table->decimal('seafront_pct', 5, 2)->default(0);
            $table->json('square_miles_json')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('unit_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('display_name');
            $table->string('class_name');
            $table->json('base_stats_json');
            $table->json('upkeep_json')->nullable();
            $table->string('unlocked_by_structure')->nullable();
            $table->timestamps();
        });

        Schema::create('nation_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nation_id')->constrained('nations')->cascadeOnDelete();
            $table->foreignId('unit_catalog_id')->nullable()->constrained('unit_catalog')->nullOnDelete();
            $table->string('custom_name')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->enum('status', ['owned', 'training'])->default('owned');
            $table->timestamp('training_ready_at')->nullable();
            $table->json('stats_override_json')->nullable();
            $table->timestamps();
        });

        Schema::create('building_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('display_name');
            $table->unsignedInteger('max_level')->default(10);
            $table->timestamps();
        });

        Schema::create('nation_buildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nation_id')->constrained('nations')->cascadeOnDelete();
            $table->foreignId('building_catalog_id')->constrained('building_catalog')->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->enum('status', ['built', 'constructing', 'upgrading'])->default('built');
            $table->timestamp('finishes_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['group', 'dm', 'announcement', 'global']);
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chat_members', function (Blueprint $table) {
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->primary(['chat_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('map_layers', function (Blueprint $table) {
            $table->id();
            $table->enum('layer_type', ['main', 'terrain', 'political'])->unique();
            $table->string('image_path');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('shop_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('display_name');
        });

        Schema::create('shop_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('shop_categories')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('display_name');
            $table->json('cost_json');
            $table->json('effect_json')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->enum('theme', ['light', 'dark'])->default('light');
            $table->enum('color_blind_mode', ['none', 'protanopia', 'deuteranopia', 'tritanopia'])->default('none');
            $table->boolean('dog_bark_enabled')->default(false);
            $table->json('extra_json')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('shop_items');
        Schema::dropIfExists('shop_categories');
        Schema::dropIfExists('map_layers');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_members');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('nation_buildings');
        Schema::dropIfExists('building_catalog');
        Schema::dropIfExists('nation_units');
        Schema::dropIfExists('unit_catalog');
        Schema::dropIfExists('nation_terrain_stats');
        Schema::dropIfExists('nation_resources');
        Schema::dropIfExists('nations');
    }
};
