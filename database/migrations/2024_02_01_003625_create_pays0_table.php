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
        Schema::create('pays0', function (Blueprint $table) {
            $table->id();
            $table->string('memid');
            $table->string('ref');
            $table->string('name');
            $table->string('time');
            $table->integer('amt');
            $table->timestamps();

            // Index on 'amt' to make summing faster
            $table->index('amt');
            // For queries based on memid
            $table->index('memid');
        });

        // Add full-text index on multiple columns
        DB::statement('ALTER TABLE pays0 ADD FULLTEXT INDEX pays0_fulltext (name, ref, memid)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pays0');
    }
};
