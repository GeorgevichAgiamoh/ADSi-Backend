<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_basic_data', function (Blueprint $table) {
            $table->string('memid')->primary();
            $table->string('fname');
            $table->string('lname');
            $table->string('mname')->nullable();
            $table->string('eml')->nullable();
            $table->string('phn');
            $table->string('verif');
            $table->string('pay');
            $table->timestamps();

            // For queries based on verif
            $table->index('verif');
            // For queries based on pay
            $table->index('pay');
        });

        // Add full-text index on multiple columns
        DB::statement('ALTER TABLE member_basic_data ADD FULLTEXT INDEX mbi_fulltext (memid, eml, phn, lname, fname)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_basic_data');
    }
};
