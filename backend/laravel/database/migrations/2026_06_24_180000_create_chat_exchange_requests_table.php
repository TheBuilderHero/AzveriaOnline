<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('chat_exchange_requests')) {
            return;
        }

        Schema::create('chat_exchange_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sender_nation_id')->constrained('nations')->cascadeOnDelete();
            $table->foreignId('recipient_nation_id')->nullable()->constrained('nations')->nullOnDelete();
            $table->json('offer_json');
            $table->json('receive_json');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'refused'])->default('pending');
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->foreignId('removed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['chat_id', 'status']);
            $table->index(['chat_id', 'recipient_nation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_exchange_requests');
    }
};
