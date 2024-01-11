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
        Schema::create('admin_user', function (Blueprint $table) {
            $table->string('memid')->primary();
            $table->string('lname');
            $table->string('oname');
            $table->string('eml');
            $table->string('role');

            $table->string('pd1');
            $table->string('pd2');
            $table->string('pp1');
            $table->string('pp2');
            $table->string('pm1');
            $table->string('pm2');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_user');
    }
};
