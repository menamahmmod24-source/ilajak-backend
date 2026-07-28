<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'medical_license_number',
        'specialization',
        'bio',
        'consultation_fee',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function clinics()
    {
        return $this->belongsToMany(Clinic::class, 'clinic_user', 'user_id', 'clinic_id', 'user_id', 'id')
                    ->wherePivot('type', 'doctor');
    }

    public function schedules()
    {
        return $this->hasMany(DoctorSchedule::class);
    }
}
