<?php

namespace Database\Seeders;

use App\Models\Medication;
use Illuminate\Database\Seeder;

class MedicationSeeder extends Seeder
{
    public function run(): void
    {
        // $medications = [
        //     // Antibiotics



        //     ['scientific_name' => 'Amoxicillin', 'commercial_name' => 'Amoxil', 'category' => 'Antibiotic', 'description' => 'مضاد حيوي واسع الطيف'],
        //     ['scientific_name' => 'Clindamycin', 'commercial_name' => 'Dalacin C', 'category' => 'Antibiotic', 'description' => 'مضاد حيوي للمرضى الذين لديهم حساسية من البنسلين'],
        //     ['scientific_name' => 'Metronidazole', 'commercial_name' => 'Flagyl', 'category' => 'Antibiotic', 'description' => 'مضاد للطفيليات والعدوى اللاهوائية'],
        //     ['scientific_name' => 'Augmentin', 'commercial_name' => 'Augmentin', 'category' => 'Antibiotic', 'description' => 'أموكسيسيلين مع كلافولانيك أسيد'],

        //     // Painkillers & Anti-inflammatory
        //     ['scientific_name' => 'Ibuprofen', 'commercial_name' => 'Brufen', 'category' => 'Painkiller', 'description' => 'مسكن ألم ومضاد التهاب'],
        //     ['scientific_name' => 'Paracetamol', 'commercial_name' => 'Panadol', 'category' => 'Painkiller', 'description' => 'مسكن ألم وخافض حرارة'],
        //     ['scientific_name' => 'Diclofenac', 'commercial_name' => 'Voltaren', 'category' => 'Painkiller', 'description' => 'مضاد التهاب قوي'],
        //     ['scientific_name' => 'Aceclofenac', 'commercial_name' => 'Airtal', 'category' => 'Painkiller', 'description' => 'مسكن ألم بعد العمليات'],

        //     // Local Anesthetics
        //     ['scientific_name' => 'Lidocaine', 'commercial_name' => 'Xylocaine', 'category' => 'Anesthetic', 'description' => 'مخدر موضعي'],
        //     ['scientific_name' => 'Articaine', 'commercial_name' => 'Septanest', 'category' => 'Anesthetic', 'description' => 'مخدر أسنان شائع'],

        //     // Others
        //     ['scientific_name' => 'Chlorhexidine', 'commercial_name' => 'Corsodyl', 'category' => 'Antiseptic', 'description' => 'غسول فم مضاد للبكتيريا'],
        //     ['scientific_name' => 'Miconazole', 'commercial_name' => 'Daktarin', 'category' => 'Antifungal', 'description' => 'مضاد للفطريات'],
        //     ['scientific_name' => 'Doxycycline', 'commercial_name' => 'Doxymycin', 'category' => 'Antibiotic', 'description' => 'مضاد حيوي لأمراض اللثة'],
        //     ['scientific_name' => 'Nystatin', 'commercial_name' => 'Mycostatin', 'category' => 'Antifungal', 'description' => 'مضاد للفطريات الفموية'],
        // ];

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
