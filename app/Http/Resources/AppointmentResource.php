<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'status'         => $this->status, // pending, confirmed, completed, canceled
            'date'           => $this->date,
            'formatted_date' => Carbon::parse($this->date)->format('M d, Y'),
            'slot_time'      => $this->slot_time,
            'notes'          => $this->notes,
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
            'created_at'     => $this->created_at->toDateTimeString(),
        ];
    }
}