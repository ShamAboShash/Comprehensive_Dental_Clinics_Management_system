<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        

        $medications = [
            ['scientific_name' => 'Amoxicillin'],
            ['scientific_name' => 'Clindamycin'],
            ['scientific_name' => 'Metronidazole'],
            ['scientific_name' => 'Augmentin'],
            ['scientific_name' => 'Ibuprofen'],
            ['scientific_name' => 'Paracetamol'],
            ['scientific_name' => 'Diclofenac'],
            ['scientific_name' => 'Aceclofenac'],
            ['scientific_name' => 'Lidocaine'],
            ['scientific_name' => 'Articaine'],
            ['scientific_name' => 'Chlorhexidine'],
            ['scientific_name' => 'Miconazole'],
            ['scientific_name' => 'Doxycycline'],
            ['scientific_name' => 'Nystatin'],
            ['scientific_name' => 'Aspirin'],
            ['scientific_name' => 'Ciprofloxacin'],
            ['scientific_name' => 'Erythromycin'],
            ['scientific_name' => 'Ketoprofen'],
            ['scientific_name' => 'Tramadol'],
            ['scientific_name' => 'Prednisolone'],
        ];

        foreach ($medications as $med) {
            Medication::create($med);
        }

        // $this->command->info('✅ ' . count($medications) . ' Medications seeded successfully!');
    }
}
