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
        Schema::create('member_general_data', function (Blueprint $table) {
            $table->string('memid')->primary();
            $table->string('sex');
            $table->string('marital');
            $table->string('dob');
            $table->string('nationality');
            $table->string('state');
            $table->string('lga');
            $table->string('town');
            $table->text('addr');
            $table->string('job');
            $table->string('nin');
            $table->string('kin_fname');
            $table->string('kin_lname');
            $table->string('kin_mname')->nullable();
            $table->string('kin_type');
            $table->string('kin_phn');
            $table->text('kin_addr');
            $table->string('kin_eml');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_general_data');
    }
};
