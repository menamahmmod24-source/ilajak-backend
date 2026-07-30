<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'doctor_id',
        'details',
        'file_path',
        'status',            // Added to track: active, completed, expired
        'remaining_refills', // Added to support the refill feature from UI
        'expires_at',        // Added to track expiration date
    ];

    /**
     * Cast attributes to native types.
     */
    protected $casts = [
        'expires_at' => 'date',
        'remaining_refills' => 'integer',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
