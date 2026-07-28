<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorProfileController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\HealthInfoController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\PatientProfileController;
use App\Http\Controllers\Api\PrescriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/doctor/register', [AuthController::class, 'registerDoctor']);

// Password Recovery
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Doctor Discovery
Route::get('/doctors', [DoctorController::class, 'index']);
Route::get('/doctors/recommended', [DoctorController::class, 'recommended']);
Route::get('/doctors/{id}', [DoctorController::class, 'show']);

// Clinic Discovery
Route::get('/clinics', [ClinicController::class, 'index']);
Route::get('/clinics/{id}', [ClinicController::class, 'show']);

// Doctor Schedules (Public View)
Route::get('/doctors/{doctorId}/schedules', [DoctorScheduleController::class, 'index']);
Route::get('/doctors/{doctorId}/available-slots', [DoctorScheduleController::class, 'availableSlots']);


/*
|--------------------------------------------------------------------------
| Protected Routes (Requires Bearer Token)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // --------------------------------------------------------------------
    // Auth Session & General User Settings
    // --------------------------------------------------------------------
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/user/change-password', [AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // --------------------------------------------------------------------
    // Patient Profile & Settings (UI Flow)
    // --------------------------------------------------------------------
    Route::get('/profile', [PatientProfileController::class, 'index']);
    Route::post('/profile/avatar', [PatientProfileController::class, 'updateAvatar']);
    Route::get('/profile/personal-info', [PatientProfileController::class, 'getPersonalInfo']);
    Route::put('/profile/personal-info', [PatientProfileController::class, 'updatePersonalInfo']);

    // Patient Health Dashboard & Overview
    Route::get('/patient/health-overview', [HealthInfoController::class, 'overview']);
    Route::get('/patient/health-info', [HealthInfoController::class, 'index']);

    // Patient Medications
    Route::post('/patient/medications', [HealthInfoController::class, 'storeMedication']);
    Route::delete('/patient/medications/{id}', [HealthInfoController::class, 'destroyMedication']);

    // Patient Allergies
    Route::post('/patient/allergies', [HealthInfoController::class, 'storeAllergy']);
    Route::delete('/patient/allergies/{id}', [HealthInfoController::class, 'destroyAllergy']);

    // Patient Chronic Conditions
    Route::post('/patient/chronic-conditions', [HealthInfoController::class, 'storeChronicCondition']);
    Route::delete('/patient/chronic-conditions/{id}', [HealthInfoController::class, 'destroyChronicCondition']);

    // Emergency Contact
    Route::put('/patient/emergency-contact', [HealthInfoController::class, 'updateEmergencyContact']);

    // --------------------------------------------------------------------
    // Appointments & Clinical Services
    // --------------------------------------------------------------------
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::patch('/appointments/{id}/status', [AppointmentController::class, 'updateStatus']);

    // Medical Records & Lab Results
    Route::get('/medical-records', [MedicalRecordController::class, 'index']);
    Route::post('/medical-records', [MedicalRecordController::class, 'store']);

    // Prescriptions
    Route::get('/prescriptions', [PrescriptionController::class, 'index']);
    Route::post('/prescriptions', [PrescriptionController::class, 'store']);

    // --------------------------------------------------------------------
    // Doctor Management Panel (For Doctor Accounts)
    // --------------------------------------------------------------------
    Route::get('/doctor/profile', [DoctorProfileController::class, 'show']);
    Route::put('/doctor/profile', [DoctorProfileController::class, 'update']);
    Route::post('/doctor/profile/avatar', [DoctorProfileController::class, 'updateAvatar']);

    // Doctor Schedules Management
    Route::post('/doctor-schedules', [DoctorScheduleController::class, 'store']);
    Route::delete('/doctor-schedules/{id}', [DoctorScheduleController::class, 'destroy']);
});