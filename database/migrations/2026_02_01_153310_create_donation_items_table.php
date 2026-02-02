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
        Schema::create('donation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('donation_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2)->comment('Price at time of donation');
            $table->decimal('total_price', 10, 2)->comment('quantity * unit_price');
            $table->timestamps();

            $table->foreign('donation_id')
                  ->references('id')
                  ->on('donations')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('restrict');

            $table->index('donation_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_items');
    }
};
