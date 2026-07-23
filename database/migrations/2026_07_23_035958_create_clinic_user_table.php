<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['admin', 'doctor']); // Distinguishes Clinic Admin vs Doctor association
            $table->timestamps();

            $table->unique(['clinic_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_user');
    }
};
