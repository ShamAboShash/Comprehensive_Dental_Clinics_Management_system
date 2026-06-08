<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\WarehouseStock;
use Illuminate\Database\Seeder;

class WarehouseStockSeeder extends Seeder
{
    public function run(): void
    {
        $materials = Material::all();

        if ($materials->isEmpty()) {
            $this->command->warn('Skipping WarehouseStockSeeder: No materials found.');
            return;
        }

        foreach ($materials as $material) {
            // create 1 to 2 batches per material
            $batchCount = rand(1, 2);

            for ($i = 0; $i < $batchCount; $i++) {
                WarehouseStock::create([
                    'material_id'     => $material->id,
                    'quantity'        => rand(10, 100),
                    'expiration_date' => now()->addMonths(rand(6, 36)),
                ]);
            }
        }

    }
}
