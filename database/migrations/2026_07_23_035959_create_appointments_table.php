<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->date('date');
            $table->time('slot_time');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'canceled'])
                  ->default('pending');
            $table->timestamps();

            // Prevents duplicate slot bookings for the same doctor at the exact same time
            $table->unique(['doctor_id', 'date', 'slot_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
