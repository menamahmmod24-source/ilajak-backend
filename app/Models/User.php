<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'national_id',
        'role',
        'status',
        'gender',
        'dob',
        'address',
        'blood_type',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
        ];
    }

    // Relationships
    public function doctorProfile()
    {
        return $this->hasOne(Doctor::class, 'user_id');
    }

    public function patientAppointments()
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    public function medicalRecords()
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id');
    }

    public function patientPrescriptions()
    {
        return $this->hasMany(Prescription::class, 'patient_id');
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_user')
                    ->withPivot('type')
                    ->withTimestamps();
    }

    // Role helper methods
    public function isSystemAdmin(): bool
    {
        return $this->role === 'system_admin';
    }

    public function isClinicAdmin(): bool
    {
        return $this->role === 'clinic_admin';
    }

    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    public function isPatient(): bool
    {
        return $this->role === 'patient';
    }
}
