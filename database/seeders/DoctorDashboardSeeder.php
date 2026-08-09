<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DoctorDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Find or create the doctor user
        $doctorUser = User::where('role', 'doctor')->first();

        if (!$doctorUser) {
            $doctorUser = User::create([
                'name'     => 'Ahmed Samy',
                'email'    => 'doctor@example.com',
                'password' => Hash::make('password'),
                'role'     => 'doctor',
                'gender'   => 'male',
                'phone'    => '01012345678',
            ]);
        }

        // 2. Ensure Doctor record exists
        $doctor = Doctor::firstOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'specialization' => 'Cardiology',
                'bio'            => 'Experienced specialist in cardiovascular conditions.',
                'experience'     => 10,
                'fees'           => 300,
            ]
        );

        // 3. Create a test Clinic if none exists
        $clinic = Clinic::firstOrCreate(
            ['name' => 'Care Central Clinic'],
            [
                'address' => '123 Health Ave, Cairo',
                'phone'   => '0223456789',
            ]
        );

        // Attach doctor to clinic
        $doctor->clinics()->syncWithoutDetaching([$clinic->id]);

        // 4. Create Doctor Schedules for everyday of the week
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach ($days as $day) {
            DoctorSchedule::firstOrCreate(
                [
                    'doctor_id'   => $doctor->id,
                    'clinic_id'   => $clinic->id,
                    'day_of_week' => $day,
                ],
                [
                    'start_time'  => '09:00:00',
                    'end_time'    => '17:00:00',
                ]
            );
        }

        // 5. Create Test Patients
        $patientsData = [
            ['name' => 'Sarah Mahmoud', 'email' => 'sarah@example.com', 'dob' => '1996-05-12', 'gender' => 'female', 'blood_type' => 'A+'],
            ['name' => 'Omar Hassan', 'email' => 'omar@example.com', 'dob' => '1990-11-23', 'gender' => 'male', 'blood_type' => 'O+'],
            ['name' => 'Nour El-Din', 'email' => 'nour@example.com', 'dob' => '2001-08-15', 'gender' => 'female', 'blood_type' => 'B-'],
            ['name' => 'Khaled Ali', 'email' => 'khaled@example.com', 'dob' => '1985-03-04', 'gender' => 'male', 'blood_type' => 'AB+'],
        ];

        $patients = [];
        foreach ($patientsData as $data) {
            $patients[] = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'       => $data['name'],
                    'password'   => Hash::make('password'),
                    'role'       => 'patient',
                    'dob'        => $data['dob'],
                    'gender'     => $data['gender'],
                    'blood_type' => $data['blood_type'],
                    'phone'      => '011' . rand(10000000, 99999999),
                ]
            );
        }

        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        // 6. Seed Today's Appointments (Updates today_appointments, pending, and completed metrics)
        Appointment::create([
            'patient_id' => $patients[0]->id,
            'doctor_id'  => $doctor->id,
            'clinic_id'  => $clinic->id,
            'date'       => $today,
            'slot_time'  => '10:00:00',
            'status'     => 'pending',
            'notes'      => 'Routine cardiovascular checkup.',
        ]);

        Appointment::create([
            'patient_id' => $patients[1]->id,
            'doctor_id'  => $doctor->id,
            'clinic_id'  => $clinic->id,
            'date'       => $today,
            'slot_time'  => '11:30:00',
            'status'     => 'confirmed',
            'notes'      => 'Follow-up on blood pressure regulation.',
        ]);

        Appointment::create([
            'patient_id' => $patients[2]->id,
            'doctor_id'  => $doctor->id,
            'clinic_id'  => $clinic->id,
            'date'       => $today,
            'slot_time'  => '14:00:00',
            'status'     => 'completed',
            'notes'      => 'ECG results reviewed. All clear.',
        ]);

        // 7. Seed Past & Future Appointments
        Appointment::create([
            'patient_id' => $patients[3]->id,
            'doctor_id'  => $doctor->id,
            'clinic_id'  => $clinic->id,
            'date'       => $yesterday,
            'slot_time'  => '15:00:00',
            'status'     => 'completed',
            'notes'      => 'Initial consultation.',
        ]);

        Appointment::create([
            'patient_id' => $patients[0]->id,
            'doctor_id'  => $doctor->id,
            'clinic_id'  => $clinic->id,
            'date'       => $tomorrow,
            'slot_time'  => '09:30:00',
            'status'     => 'pending',
        ]);
    }
}
