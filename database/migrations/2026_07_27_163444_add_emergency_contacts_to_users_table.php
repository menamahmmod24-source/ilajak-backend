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
    Schema::table('users', function (Blueprint $table) {
        $table->string('emergency_contact_name')->nullable();
        $table->string('emergency_contact_relation')->nullable();
        $table->string('emergency_contact_phone')->nullable();
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn([
            'emergency_contact_name',
            'emergency_contact_relation',
            'emergency_contact_phone',
        ]);
    });
}
};
