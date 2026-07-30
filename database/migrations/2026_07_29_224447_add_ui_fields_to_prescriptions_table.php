<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('status')->default('active')->after('file_path');
            $table->integer('remaining_refills')->default(0)->after('status');
            $table->date('expires_at')->nullable()->after('remaining_refills');
        });
    }

    public function down(): void
    {
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['status', 'remaining_refills', 'expires_at']);
        });
    }
};
