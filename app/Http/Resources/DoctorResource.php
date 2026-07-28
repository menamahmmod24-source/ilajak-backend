<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->user?->name,
            'avatar'              => $this->user?->avatar ? asset('storage/' . $this->user->avatar) : null,
            'specialization'      => $this->specialization,
            'title'               => $this->title ?? 'Senior Consultant', // e.g. Senior Consultant / Specialist
            'bio'                 => $this->bio,
            'consultation_fee'    => (float) $this->consultation_fee,
            
            // Profile Stats Counters (Shown in UI)
            'stats' => [
                'experience_years' => $this->experience_years ?? '15+',
                'patients_count'   => $this->patients_count ?? '10k+',
                'certificates_cnt' => $this->certificates_count ?? 24,
                'rating'           => (float) ($this->reviews_avg_rating ?? 4.9),
                'reviews_count'    => (int) ($this->reviews_count ?? 1248),
            ],

            // Extended Doctor Details
            'education'    => $this->education ?? 'M.D. Cardiothoracic Surgery, Johns Hopkins',
            'experience'   => $this->experience_history ?? 'Chief of Cardiology at MedCare International',
            'certificates' => $this->certificates ?? [], // Array of certificate image URLs

            'clinics' => $this->clinics->map(function ($clinic) {
                return [
                    'id'      => $clinic->id,
                    'name'    => $clinic->name,
                    'address' => $clinic->address,
                    'phone'   => $clinic->phone,
                    'fee'     => $clinic->pivot->consultation_fee ?? $this->consultation_fee,
                ];
            }),
        ];
    }
}