<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'chronic_diseases',
        'allergies',
        'lab_results',
        'radiology_results',
        'attachments',
    ];

    protected $casts = [
        'lab_results' => 'array',
        'radiology_results' => 'array',
        'attachments' => 'array',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
