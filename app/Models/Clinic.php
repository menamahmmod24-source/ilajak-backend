<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'max_doctors',
        'status',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'clinic_user')
                    ->withPivot('type')
                    ->withTimestamps();
    }

    public function clinicAdmins()
    {
        return $this->belongsToMany(User::class, 'clinic_user')
                    ->wherePivot('type', 'admin')
                    ->withTimestamps();
    }

    public function doctors()
    {
        return $this->belongsToMany(User::class, 'clinic_user')
                    ->wherePivot('type', 'doctor')
                    ->withTimestamps();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
