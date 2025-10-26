<?php

namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CostCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $categories = [
            // Fixed Costs
            ['name' => 'Land Rent/Mortgage', 'type' => 'fixed', 'description' => 'Land rental or mortgage payments'],
            ['name' => 'Property Taxes', 'type' => 'fixed', 'description' => 'Annual property taxes'],
            ['name' => 'Equipment Depreciation', 'type' => 'fixed', 'description' => 'Machinery and equipment depreciation'],
            ['name' => 'Insurance', 'type' => 'fixed', 'description' => 'Property and equipment insurance'],
            ['name' => 'Permanent Labor', 'type' => 'fixed', 'description' => 'Full-time employee wages and benefits'],

            // Variable Costs
            ['name' => 'Seeds/Seedlings', 'type' => 'variable', 'description' => 'Seeds, seedlings, or planting materials'],
            ['name' => 'Fertilizers', 'type' => 'variable', 'description' => 'Fertilizers and soil amendments'],
            ['name' => 'Pesticides', 'type' => 'variable', 'description' => 'Pesticides and herbicides'],
            ['name' => 'Fuel', 'type' => 'variable', 'description' => 'Fuel and energy costs'],
            ['name' => 'Seasonal Labor', 'type' => 'variable', 'description' => 'Temporary and seasonal workers'],
            ['name' => 'Equipment Repairs', 'type' => 'variable', 'description' => 'Machinery repairs and maintenance'],
            ['name' => 'Transportation', 'type' => 'variable', 'description' => 'Shipping and transportation costs'],
            ['name' => 'Water/Irrigation', 'type' => 'variable', 'description' => 'Water and irrigation costs'],
        ];

        foreach ($categories as $category) {
            CostCategory::create($category);
        }
    }
}
