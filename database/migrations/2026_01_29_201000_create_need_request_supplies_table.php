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
        Schema::create('need_request_supplies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('need_request_id');
            $table->enum('product_type', [
                'dry_food',
                'hot_food',
                'miswak',
                'prayer_mat',
                'prayer_sheets',
                'prayer_towels',
                'quran',
                'quran_holder',
                'tissues',
            ]);
            $table->unsignedInteger('requested_quantity');

            $table->timestamps();

            $table->foreign('need_request_id')
                ->references('id')
                ->on('need_requests')
                ->onDelete('cascade');

            $table->index('product_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('need_request_supplies');
    }
};


