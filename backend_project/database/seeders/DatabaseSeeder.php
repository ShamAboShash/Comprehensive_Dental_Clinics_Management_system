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
            AppointmentTypeSeeder::class,
            TreatmentTypeSeeder::class,
            ProcedureSeeder::class,
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
            ClinicSeeder::class,
            TreatmentSeeder::class,
            TreatmentSessionSeeder::class,
            PrescriptionSeeder::class,
            // TreatmentPhotoSeeder::class,
            LabOrderTypeSeeder::class,
            LabOrderSeeder::class,
            MaterialSeeder::class,
            WarehouseStockSeeder::class,
            LabStockSeeder::class,
        ]);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
