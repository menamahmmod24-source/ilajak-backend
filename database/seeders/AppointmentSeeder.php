<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appointments = [
            [
                'patient_id' => 1,
                'doctor_id'  => 4,
                'clinic_id'  => 1,
                'date'       => '2026-08-12',
                'slot_time'  => '09:00',
                'status'     => 'confirmed',
                'notes'      => 'Regular follow-up checkup.',
            ],
            [
                'patient_id' => 2,
                'doctor_id'  => 4,
                'clinic_id'  => 1,
                'date'       => '2026-08-12',
                'slot_time'  => '09:30',
                'status'     => 'pending',
                'notes'      => 'First time visit.',
            ],
            [
                'patient_id' => 1,
                'doctor_id'  => 4,
                'clinic_id'  => 1,
                'date'       => '2026-08-10',
                'slot_time'  => '10:00',
                'status'     => 'completed',
                'notes'      => 'Patient reported mild symptoms.',
            ],
        ];

        foreach ($appointments as $data) {
            Appointment::updateOrCreate(
                [
                    'doctor_id' => $data['doctor_id'],
                    'date'      => $data['date'],
                    'slot_time' => $data['slot_time'],
                ],
                $data
            );
        }
    }
}
