<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = ['user_id', 'name', 'dosage', 'frequency'];
}
