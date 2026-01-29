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
        Schema::create('trucks', function (Blueprint $table) {
            $table->id();
            $table->string('truck_id', 50)->unique();
            $table->string('name');
            $table->unsignedInteger('capacity')->comment('Water capacity in liters');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->timestamp('last_location_update')->nullable();
            $table->unsignedBigInteger('assigned_driver_id')->nullable();
            $table->timestamps();
            
            $table->foreign('assigned_driver_id')->references('id')->on('users')->onDelete('set null');
            $table->index('truck_id', 'idx_truck_id');
            $table->index('status', 'idx_status');
            $table->index(['current_latitude', 'current_longitude'], 'idx_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};

