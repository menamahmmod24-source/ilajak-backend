<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            // Make day_of_week nullable so specific date schedules don't require it
            $table->string('day_of_week')->nullable()->change();

            // Add specific date support
            $table->date('specific_date')->nullable()->after('day_of_week');

            // Add flag to mark specific dates as off/cancelled
            $table->boolean('is_day_off')->default(false)->after('slot_duration_minutes');

            // Add indexes for optimal query performance
            $table->index(['doctor_id', 'day_of_week']);
            $table->index(['doctor_id', 'specific_date']);
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropIndex(['doctor_id', 'day_of_week']);
            $table->dropIndex(['doctor_id', 'specific_date']);

            $table->dropColumn(['specific_date', 'is_day_off']);

            $table->string('day_of_week')->nullable(false)->change();
        });
    }
};
