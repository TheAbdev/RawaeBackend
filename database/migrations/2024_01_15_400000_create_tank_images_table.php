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
        Schema::create('tank_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mosque_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('image_path', 500);
            $table->string('image_url', 500);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('mosque_id')->references('id')->on('mosques')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');
            $table->index('mosque_id', 'idx_mosque');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tank_images');
    }
};

