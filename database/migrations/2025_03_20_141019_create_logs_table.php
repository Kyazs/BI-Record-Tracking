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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Who made the change?
            $table->unsignedBigInteger('applicant_id'); // Which applicant was modified?
            $table->string('action'); // e.g., "created", "updated", "deleted"
            $table->json('old_data')->nullable(); // Before update/delete
            $table->json('new_data')->nullable(); // After update
            $table->timestamps(); // When the action happened
    
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('applicant_id')->references('id')->on('applicants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
