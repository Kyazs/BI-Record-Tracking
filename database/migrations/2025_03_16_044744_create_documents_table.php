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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->onDelete('cascade');
            // Barangay Certification
            $table->string('barangay_cert_path')->nullable();
            // PNP Clearance
            $table->string('pnp_clearance_path')->nullable();
            // RTC/MPC Clearance
            $table->string('rtc_clearance_path')->nullable();
            // School Certification
            $table->string('school_cert_path')->nullable();
            // derogratory records
            $table->string('derogatory_records_path')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
