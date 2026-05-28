<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            DiseaseCategorySeeder::class,
            SpecializationSeeder::class,
            UserSeeder::class,
            MedicationSeeder::class,
            EmployeeSeeder::class,
            EmployeeEducationSeeder::class,
            EmployeeExperienceSeeder::class,
            EmployeeSkillSeeder::class,
            EmployeeTrainingSeeder::class,
            PatientSeeder::class,
            PatientAllergySeeder::class,
            PatientChronicDiseaseSeeder::class,
            PatientMedicationSeeder::class,
            DentistSeeder::class,
        ]);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
