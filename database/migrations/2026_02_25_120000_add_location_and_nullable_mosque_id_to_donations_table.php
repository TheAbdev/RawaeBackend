<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['mosque_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->string('location', 500)->nullable()->after('mosque_id');
            $table->unsignedBigInteger('mosque_id')->nullable()->change();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('mosque_id')->references('id')->on('mosques')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign(['mosque_id']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->unsignedBigInteger('mosque_id')->nullable(false)->change();
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreign('mosque_id')->references('id')->on('mosques')->onDelete('cascade');
        });
    }
};
