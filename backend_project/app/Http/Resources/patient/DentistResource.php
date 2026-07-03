<?php

namespace App\Http\Resources\patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DentistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->employee->user->name ?? null,
            'profile_picture'    => $this->employee->user->profile_picture
                ? Storage::url($this->employee->user->profile_picture)
                : null,
            'specialization'     => $this->specialization ? [
                'ar' => $this->specialization->name,
                'en' => $this->specialization->name_en,
            ] : null,
            'years_of_experience' => $this->years_of_experience,
            'average_rating'      => $this->average_rating,
            'bio'                 => $this->bio,
        ];
    }
}
