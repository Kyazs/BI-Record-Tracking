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
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->integer('age');
            $table->string('address');
            $table->date('date_of_birth');
            $table->string('birth_place');
            $table->string('school');
            $table->string('officer_name');            
            $table->enum('status', ['Cleared', 'Not Cleared', 'Pending'])->default('Pending');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
