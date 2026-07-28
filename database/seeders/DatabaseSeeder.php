<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\DoctorSchedule;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with complete test data.
     */
    public function run(): void
    {
        // -------------------------------------------------------------
        // 1. Create System Admin & Clinic Admin Users
        // -------------------------------------------------------------
        $systemAdmin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@3ilajak.com',
            'password' => Hash::make('password123'),
            'phone' => '01000000000',
            'national_id' => '10000000000000',
            'role' => 'system_admin',
            'status' => 'active',
            'gender' => 'male',
        ]);

        $clinicAdminUser = User::create([
            'name' => 'Clinic Manager',
            'email' => 'clinicadmin@3ilajak.com',
            'password' => Hash::make('password123'),
            'phone' => '01011111111',
            'national_id' => '20000000000000',
            'role' => 'clinic_admin',
            'status' => 'active',
            'gender' => 'female',
        ]);

        // -------------------------------------------------------------
        // 2. Create Clinics
        // -------------------------------------------------------------
        $clinic1 = Clinic::create([
            'name' => 'Al-Amal Medical Center',
            'address' => 'Nasr City, Cairo',
            'phone' => '0223456789',
            'max_doctors' => 10,
            'status' => 'active',
        ]);

        $clinic2 = Clinic::create([
            'name' => 'El-Shifa Multi-Speciality Clinic',
            'address' => 'Dokki, Giza',
            'phone' => '0237654321',
            'max_doctors' => 5,
            'status' => 'active',
        ]);

        // Attach Clinic Admin to Clinic 1 via clinic_user pivot
        $clinic1->users()->attach($clinicAdminUser->id, ['type' => 'admin']);

        // -------------------------------------------------------------
        // 3. Create Doctors & Profiles
        // -------------------------------------------------------------
        $doctorUser1 = User::create([
            'name' => 'Dr. Ahmed Samy',
            'email' => 'drahmed@3ilajak.com',
            'password' => Hash::make('password123'),
            'phone' => '01112345678',
            'national_id' => '30000000000001',
            'role' => 'doctor',
            'status' => 'active',
            'gender' => 'male',
            'address' => 'Cairo, Egypt',
        ]);

        $doctor1 = Doctor::create([
            'user_id' => $doctorUser1->id,
            'medical_license_number' => 'EGY-LIC-1001',
            'specialization' => 'Cardiology',
            'bio' => 'Senior Consultant Cardiologist with over 12 years of experience.',
            'consultation_fee' => 350.00,
        ]);

        $clinic1->users()->attach($doctorUser1->id, ['type' => 'doctor']);

        $doctorUser2 = User::create([
            'name' => 'Dr. Sarah Hassan',
            'email' => 'drsarah@3ilajak.com',
            'password' => Hash::make('password123'),
            'phone' => '01212345678',
            'national_id' => '30000000000002',
            'role' => 'doctor',
            'status' => 'active',
            'gender' => 'female',
            'address' => 'Giza, Egypt',
        ]);

        $doctor2 = Doctor::create([
            'user_id' => $doctorUser2->id,
            'medical_license_number' => 'EGY-LIC-1002',
            'specialization' => 'Pediatrics',
            'bio' => 'Pediatric Consultant specializing in early child wellness.',
            'consultation_fee' => 250.00,
        ]);

        $clinic2->users()->attach($doctorUser2->id, ['type' => 'doctor']);

        // -------------------------------------------------------------
        // 4. Create Patients & Medical Records
        // -------------------------------------------------------------
        $patient = User::create([
            'name' => 'John Doe',
            'email' => 'patient@3ilajak.com',
            'password' => Hash::make('password123'),
            'phone' => '01099998888',
            'national_id' => '40000000000001',
            'role' => 'patient',
            'status' => 'active',
            'gender' => 'male',
            'dob' => '1995-05-15',
            'address' => 'Maadi, Cairo',
            'blood_type' => 'A+',
        ]);

        MedicalRecord::create([
            'patient_id' => $patient->id,
            'chronic_diseases' => 'Mild Hypertension',
            'allergies' => 'Penicillin',
            'lab_results' => [
                'blood_pressure' => '130/85',
                'cholesterol' => '190 mg/dL',
            ],
            'radiology_results' => [
                'chest_xray' => 'Clear',
            ],
            'attachments' => [
                'report_2026.pdf',
            ],
        ]);

        // -------------------------------------------------------------
        // 5. Create Doctor Schedules
        // -------------------------------------------------------------
        DoctorSchedule::create([
            'doctor_id' => $doctor1->id,
            'clinic_id' => $clinic1->id,
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '15:00:00',
            'slot_duration_minutes' => 30,
        ]);

        DoctorSchedule::create([
            'doctor_id' => $doctor2->id,
            'clinic_id' => $clinic2->id,
            'day_of_week' => 'Wednesday',
            'start_time' => '10:00:00',
            'end_time' => '14:00:00',
            'slot_duration_minutes' => 20,
        ]);

        // -------------------------------------------------------------
        // 6. Create Sample Appointment & Prescription
        // -------------------------------------------------------------
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'clinic_id' => $clinic1->id,
            'date' => '2026-08-03',
            'slot_time' => '10:00:00',
            'status' => 'confirmed',
        ]);

        Prescription::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor1->id,
            'details' => 'Concor 5mg - Take 1 tablet daily after breakfast.',
            'file_path' => 'prescriptions/rx_1001.pdf',
        ]);
    }
}
