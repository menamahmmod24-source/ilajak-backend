<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('national_id')->nullable()->unique()->after('phone');
            $table->enum('role', ['system_admin', 'clinic_admin', 'doctor', 'patient'])
                  ->default('patient')
                  ->after('password');
            $table->enum('status', ['active', 'suspended'])->default('active')->after('role');
            $table->enum('gender', ['male', 'female'])->nullable()->after('status');
            $table->date('dob')->nullable()->after('gender');
            $table->string('address')->nullable()->after('dob');
            $table->string('blood_type')->nullable()->after('address'); // e.g., A+, O-
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'national_id', 'role', 'status',
                'gender', 'dob', 'address', 'blood_type'
            ]);
        });
    }
};
