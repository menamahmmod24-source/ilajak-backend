<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DoctorSchedule;
use Carbon\Carbon;

class DoctorScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Weekly recurring schedules for Doctor ID 4 at Clinic ID 1
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            DoctorSchedule::updateOrCreate(
                [
                    'doctor_id'     => 4,
                    'clinic_id'     => 1,
                    'day_of_week'   => $day,
                    'specific_date' => null,
                ],
                [
                    'start_time'            => '09:00',
                    'end_time'              => '17:00',
                    'slot_duration_minutes' => 30,
                    'is_day_off'            => $day === 'Friday', // Set Friday as day off
                ]
            );
        }

        // 2. Specific Date Overrides for Doctor ID 4

        // Target Date 1: 2026-08-12 (Custom schedule override for Wednesday)
        DoctorSchedule::updateOrCreate(
            [
                'doctor_id'     => 4,
                'clinic_id'     => 1,
                'specific_date' => '2026-08-12',
            ],
            [
                'day_of_week'           => 'Wednesday',
                'start_time'            => '08:00',
                'end_time'              => '14:00',
                'slot_duration_minutes' => 30,
                'is_day_off'            => false,
            ]
        );

        // Target Date 2: 2026-08-15 (Specific Day Off)
        DoctorSchedule::updateOrCreate(
            [
                'doctor_id'     => 4,
                'clinic_id'     => 1,
                'specific_date' => '2026-08-15',
            ],
            [
                'day_of_week'           => 'Saturday',
                'start_time'            => '09:00',
                'end_time'              => '17:00',
                'slot_duration_minutes' => 30,
                'is_day_off'            => true,
            ]
        );
    }
}
