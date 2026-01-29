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
        Schema::create('mosques', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedInteger('capacity')->comment('Water capacity in liters');
            $table->unsignedInteger('current_water_level')->default(0);
            $table->unsignedInteger('required_water_level');
            $table->enum('need_level', ['Low', 'Medium', 'High'])->default('Medium');
            $table->unsignedInteger('need_score')->default(0)->comment('AI calculated need score (0-100)');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('mosque_admin_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('mosque_admin_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['latitude', 'longitude'], 'idx_location');
            $table->index('need_score', 'idx_need_score');
            $table->index('mosque_admin_id', 'idx_mosque_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mosques');
    }
};

