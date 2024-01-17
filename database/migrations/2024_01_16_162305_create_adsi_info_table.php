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
        Schema::create('adsi_info', function (Blueprint $table) {
            $table->string('memid')->primary();
            $table->string('cname');
            $table->string('regno');
            $table->text('addr');
            $table->string('nationality');
            $table->string('state');
            $table->string('lga');
            $table->string('aname');
            $table->string('anum');
            $table->string('bnk');
            $table->string('pname');
            $table->string('peml');
            $table->string('pphn');
            $table->string('paddr');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adsi_info');
    }
};
