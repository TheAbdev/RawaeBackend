<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the role enum to include 'driver'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'auditor', 'investor', 'donor', 'mosque_admin', 'logistics_supervisor', 'driver')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'driver' from the enum (revert to original)
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'auditor', 'investor', 'donor', 'mosque_admin', 'logistics_supervisor')");
    }
};
