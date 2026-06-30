<?php

namespace Database\Seeders;

use App\Models\DrugInteraction;
use App\Models\Medication;
use Illuminate\Database\Seeder;

class DrugInteractionSeeder extends Seeder
{
    public function run(): void
    {
        // Helper: find medication ID by scientific name
        $id = fn(string $name) => Medication::where('scientific_name', $name)->value('id');

        $interactions = [
            // -------------------------------------------------------
            // Ibuprofen interactions
            // -------------------------------------------------------
            [
                'medication_id_1' => $id('Ibuprofen'),
                'medication_id_2' => $id('Aspirin'),
                'severity'        => 'moderate',
                'description'     => 'Both are NSAIDs — combining them increases the risk of gastrointestinal bleeding.',
            ],
            [
                'medication_id_1' => $id('Ibuprofen'),
                'medication_id_2' => $id('Warfarin'),
                'severity'        => 'severe',
                'description'     => 'Ibuprofen can enhance the anticoagulant effect of Warfarin, increasing bleeding risk.',
            ],
            [
                'medication_id_1' => $id('Ibuprofen'),
                'medication_id_2' => $id('Methotrexate'),
                'severity'        => 'severe',
                'description'     => 'NSAIDs can reduce Methotrexate excretion, leading to toxicity.',
            ],

            // -------------------------------------------------------
            // Amoxicillin interactions
            // -------------------------------------------------------
            [
                'medication_id_1' => $id('Amoxicillin'),
                'medication_id_2' => $id('Warfarin'),
                'severity'        => 'moderate',
                'description'     => 'Amoxicillin may enhance the anticoagulant effect of Warfarin.',
            ],
            [
                'medication_id_1' => $id('Amoxicillin'),
                'medication_id_2' => $id('Methotrexate'),
                'severity'        => 'moderate',
                'description'     => 'Penicillins may reduce renal clearance of Methotrexate.',
            ],

            // -------------------------------------------------------
            // Metronidazole interactions (very common in dentistry)
            // -------------------------------------------------------
            [
                'medication_id_1' => $id('Metronidazole'),
                'medication_id_2' => $id('Warfarin'),
                'severity'        => 'severe',
                'description'     => 'Metronidazole significantly enhances the anticoagulant effect of Warfarin.',
            ],
            [
                'medication_id_1' => $id('Metronidazole'),
                'medication_id_2' => $id('Lithium'),
                'severity'        => 'severe',
                'description'     => 'Metronidazole can increase Lithium levels, leading to toxicity.',
            ],
            [
                'medication_id_1' => $id('Metronidazole'),
                'medication_id_2' => $id('Alcohol'),
                'severity'        => 'severe',
                'description'     => 'Disulfiram-like reaction: nausea, vomiting, flushing.',
            ],

            // -------------------------------------------------------
            // Clindamycin interactions
            // -------------------------------------------------------
            [
                'medication_id_1' => $id('Clindamycin'),
                'medication_id_2' => $id('Erythromycin'),
                'severity'        => 'moderate',
                'description'     => 'Antagonistic effect — both compete for the same ribosomal binding site.',
            ],

            // -------------------------------------------------------
            // Diclofenac interactions
            // -------------------------------------------------------
            [
                'medication_id_1' => $id('Diclofenac'),
                'medication_id_2' => $id('Warfarin'),
                'severity'        => 'severe',
                'description'     => 'Diclofenac can enhance the anticoagulant effect of Warfarin.',
            ],
            [
                'medication_id_1' => $id('Diclofenac'),
                'medication_id_2' => $id('Aspirin'),
                'severity'        => 'moderate',
                'description'     => 'Combined NSAIDs increase gastrointestinal bleeding risk.',
            ],

            // -------------------------------------------------------
            // Codeine interactions
            // -------------------------------------------------------
            [
                'medication_id_1' => $id('Codeine'),
                'medication_id_2' => $id('Tramadol'),
                'severity'        => 'severe',
                'description'     => 'Both are opioids — combining them significantly increases respiratory depression risk.',
            ],
            [
                'medication_id_1' => $id('Codeine'),
                'medication_id_2' => $id('Diazepam'),
                'severity'        => 'severe',
                'description'     => 'CNS depression risk increases significantly when opioids are combined with benzodiazepines.',
            ],
        ];

        foreach ($interactions as $interaction) {
            // Skip if either medication does not exist in the DB yet
            if (!$interaction['medication_id_1'] || !$interaction['medication_id_2']) {
                continue;
            }

            DrugInteraction::updateOrCreate(
                [
                    'medication_id_1' => $interaction['medication_id_1'],
                    'medication_id_2' => $interaction['medication_id_2'],
                ],
                [
                    'severity'    => $interaction['severity'],
                    'description' => $interaction['description'],
                ]
            );
        }
    }
}
