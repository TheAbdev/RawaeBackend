<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['donation', 'delivery', 'mosque', 'user', 'campaign', 'other']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('related_id')->nullable()->comment('ID of related entity');
            $table->string('related_type', 100)->nullable()->comment('Model name of related entity');
            $table->text('message_ar');
            $table->text('message_en');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();
            
            $table->index('type', 'idx_type');
            $table->index('user_id', 'idx_user');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

