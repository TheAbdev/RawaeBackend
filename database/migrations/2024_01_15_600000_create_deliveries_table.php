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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('truck_id');
            $table->unsignedBigInteger('mosque_id');
            $table->unsignedBigInteger('need_request_id')->nullable();
            $table->unsignedInteger('liters_delivered');
            $table->decimal('delivery_latitude', 10, 8)->nullable();
            $table->decimal('delivery_longitude', 11, 8)->nullable();
            $table->string('proof_image_path', 500)->nullable();
            $table->string('proof_image_url', 500)->nullable();
            $table->enum('status', ['pending', 'in-transit', 'delivered', 'cancelled'])->default('pending');
            $table->date('expected_delivery_date')->nullable();
            $table->timestamp('actual_delivery_date')->nullable();
            $table->unsignedBigInteger('delivered_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('truck_id')->references('id')->on('trucks')->onDelete('cascade');
            $table->foreign('mosque_id')->references('id')->on('mosques')->onDelete('cascade');
            $table->foreign('need_request_id')->references('id')->on('need_requests')->onDelete('set null');
            $table->foreign('delivered_by')->references('id')->on('users')->onDelete('set null');
            $table->index('truck_id', 'idx_truck');
            $table->index('mosque_id', 'idx_mosque');
            $table->index('status', 'idx_status');
            $table->index('actual_delivery_date', 'idx_delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};

