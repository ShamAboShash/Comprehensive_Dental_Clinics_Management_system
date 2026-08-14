<?php

namespace Database\Seeders;

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
    EmployeeWorkScheduleSeeder::class,   // ← ADDED
    ClinicSeeder::class,

    TreatmentSeeder::class,
    TreatmentSessionSeeder::class,
    PrescriptionSeeder::class,
    // TreatmentPhotoSeeder::class,      // ← still commented
    ToothTreatmentSeeder::class,

    LabOrderTypeCategorySeeder::class,
    LabOrderTypeSeeder::class,
    MaterialSeeder::class,               // ← moved before LabOrder
    LabOrderSeeder::class,
    WarehouseStockSeeder::class,
    LabStockSeeder::class,
    MaterialRequestSeeder::class,        // ← ADDED

    DrugInteractionSeeder::class,
    CenterInfoSeeder::class,
    ComplaintSeeder::class,

    InvoiceSeeder::class,
    PaymentSeeder::class,
    CenterOfferSeeder::class,

    AttendanceSeeder::class,             // ← moved to end
]);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
