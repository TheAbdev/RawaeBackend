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
        Schema::create('mosque_supplies', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('mosque_id');

            $table->enum('product_type', [
                'dry_food',
                'hot_food',
                'miswak',
                'prayer_mat',
                'prayer_sheets',
                'prayer_towels',
                'quran',
                'quran_holder',
                'tissues'
            ]);

            $table->unsignedInteger('current_quantity')->default(0);
            $table->unsignedInteger('required_quantity');

            $table->enum('need_level', ['Low', 'Medium', 'High'])->default('Medium');
            $table->unsignedInteger('need_score')->default(0)->comment('AI calculated need score (0-100)');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('mosque_id')
                  ->references('id')
                  ->on('mosques')
                  ->onDelete('cascade');

            $table->unique(['mosque_id', 'product_type']);
            $table->index('need_score');
            $table->index('product_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mosque_supplies');
    }
};
