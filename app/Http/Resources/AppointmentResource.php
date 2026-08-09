<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dateObj = $this->date ? Carbon::parse($this->date) : null;

        return [
            'id'             => $this->id,
            'status'         => $this->status, // pending, confirmed, completed, canceled
            'date'           => $dateObj?->format('Y-m-d'),
            'day_of_week'    => $dateObj?->format('l'),                 // "Monday"
            'formatted_date' => $dateObj?->format('l, M d, Y'),          // "Monday, Aug 10, 2026"
            'slot_time'      => $this->slot_time ? Carbon::parse($this->slot_time)->format('H:i') : null,
            'formatted_time' => $this->slot_time ? Carbon::parse($this->slot_time)->format('h:i A') : null, // "08:00 AM"
            'notes'          => $this->notes,
            'patient'        => [
                'id'         => $this->patient?->id,
                'name'       => $this->patient?->name,
                'phone'      => $this->patient?->phone,
                'gender'     => $this->patient?->gender,
                'blood_type' => $this->patient?->blood_type,
                'age'        => $this->patient?->dob ? Carbon::parse($this->patient->dob)->age : null,
                'avatar'     => $this->patient?->avatar ? asset('storage/' . $this->patient->avatar) : null,
            ],
            'doctor'         => [
                'id'             => $this->doctor?->id,
                'name'           => $this->doctor?->user?->name,
                'specialization' => $this->doctor?->specialization,
                'avatar'         => $this->doctor?->user?->avatar ? asset('storage/' . $this->doctor->user->avatar) : null,
            ],
            'clinic'         => [
                'id'      => $this->clinic?->id,
                'name'    => $this->clinic?->name,
                'address' => $this->clinic?->address,
            ],
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
