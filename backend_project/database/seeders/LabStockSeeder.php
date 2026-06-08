<?php

namespace Database\Seeders;

use App\Models\LabStock;
use App\Models\Material;
use Illuminate\Database\Seeder;

class LabStockSeeder extends Seeder
{
    public function run(): void
    {
        // only seed lab materials
        $materials = Material::whereIn('category', ['lab', 'both'])->get();

        if ($materials->isEmpty()) {
            $this->command->warn('Skipping LabStockSeeder: No materials found.');
            return;
        }

        foreach ($materials as $material) {
            $batchCount = rand(1, 2);

            for ($i = 0; $i < $batchCount; $i++) {
                LabStock::create([
                    'material_id'     => $material->id,
                    'quantity'        => rand(5, 50),
                    'expiration_date' => now()->addMonths(rand(6, 24)),
                ]);
            }
        }

    }
}
