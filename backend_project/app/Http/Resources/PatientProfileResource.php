<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientProfileResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id'          => $this->id,
            'user_id'     => $this->user_id,

            // ==================== User Information ====================
            'user' => [
                'id'                => $this->user?->id,
                'name'              => $this->user?->name,
                'email'             => $this->user?->email,
                'phone'             => $this->user?->phone,
                'date_of_birth'     => $this->user?->date_of_birth,
                'gender'            => $this->user?->gender,
                'address'           => $this->user?->address,
                'profile_picture'   => $this->user?->profile_picture_url,
                'is_active'         => $this->user?->is_active ?? false,
                'email_verified_at' => $this->user?->email_verified_at,
            ],

            // ==================== Basic Patient Information ====================
            'emergency_contact_name'    => $this->emergency_contact_name,
            'emergency_contact_relation'=> $this->emergency_contact_relation,
            'emergency_contact_phone'   => $this->emergency_contact_phone,
            'is_pregnant'               => $this->is_pregnant,
            'is_breastfeeding'          => $this->is_breastfeeding,
            'is_smoker'                 => $this->is_smoker,
            'drinks_alcohol_frequently' => $this->drinks_alcohol_frequently,
            'teeth_cleaning_frequency'  => $this->teeth_cleaning_frequency,

            // ==================== Relationships (Fixed) ====================
            'allergies' => PatientAllergyResource::collection($this->whenLoaded('allergies')),

            'chronic_diseases' => PatientChronicDiseaseResource::collection(
                $this->whenLoaded('chronicDiseases')
            ),

            'medications' => PatientMedicationResource::collection(
                $this->whenLoaded('medications')
            ),
        ];
    }
}



class PatientAllergyResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id'           => $this->id,
            'allergy_name' => $this->allergy_name,
        ];
    }
}

class PatientChronicDiseaseResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id'           => $this->id,
            'category'     => $this->category?->name,
            'category_en'  => $this->category?->name_en,
            'problem_name' => $this->problem_name,
            'details'      => $this->details,
            'severity'     => $this->severity,
        ];
    }
}

class PatientMedicationResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'id'          => $this->id,
            'dosage'      => $this->dosage,
            'frequency'   => $this->frequency,
            'notes'       => $this->notes,
            'medication'  => $this->medication?->scientific_name,
            // 'medication'  => $this->whenLoaded('medication', fn() => [
            //     'id'   => $this->medication?->id,
            //     'name' => $this->medication?->name,
            // ]),
        ];
    }
}
