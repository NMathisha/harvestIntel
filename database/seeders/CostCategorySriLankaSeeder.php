<?php

/*
=============================================================================
SRI LANKAN FARMING OPERATIONS SEEDERS
=============================================================================

This seeder creates realistic historical data for Sri Lankan farming operations
including:
- Rice (Paddy) - Main crop in all provinces
- Tea - Hill country (Central, Sabaragamuwa, Uva)
- Coconut - Coastal areas
- Rubber - Wet zone
- Vegetables - Throughout the island
- Spices (Cinnamon, Pepper, Cardamom)
- Fruits (Mango, Banana, Papaya)

Regional considerations:
- Western Province: Higher costs due to urban proximity
- Central Province: Tea-focused, higher labor costs
- Southern Province: Mixed farming, coconut
- Northern Province: Rice and vegetables
- Eastern Province: Rice and mixed farming
- North Western Province: Coconut triangle
- North Central Province: Rice bowl of Sri Lanka
- Uva Province: Tea and vegetables
- Sabaragamuwa Province: Tea, rubber, rice

Currency: Sri Lankan Rupees (LKR)
*/

// Database Seeder: CostCategorySriLankaSeeder.php
namespace Database\Seeders;

use App\Models\CostCategory;
use Illuminate\Database\Seeder;

class CostCategorySriLankaSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Fixed Costs
            [
                'name' => 'Land Rent/Lease',
                'type' => 'fixed',
                'description' => 'Land rental or lease payments (annual)',
                'is_predictable' => true,
                'typical_percentage' => 15.0
            ],
            [
                'name' => 'Property Taxes/Rates',
                'type' => 'fixed',
                'description' => 'Government taxes and local authority rates',
                'is_predictable' => true,
                'typical_percentage' => 2.0
            ],
            [
                'name' => 'Equipment/Machinery',
                'type' => 'fixed',
                'description' => 'Tractors, tillers, threshing machines',
                'is_predictable' => true,
                'typical_percentage' => 12.0
            ],
            [
                'name' => 'Insurance',
                'type' => 'fixed',
                'description' => 'Crop insurance and equipment insurance',
                'is_predictable' => true,
                'typical_percentage' => 3.0
            ],
            [
                'name' => 'Permanent Labor',
                'type' => 'fixed',
                'description' => 'Full-time farm workers annual wages',
                'is_predictable' => true,
                'typical_percentage' => 20.0
            ],

            // Variable Costs
            [
                'name' => 'Seeds/Seedlings',
                'type' => 'variable',
                'description' => 'Certified seeds, seedlings, planting material',
                'is_predictable' => true,
                'typical_percentage' => 8.0
            ],
            [
                'name' => 'Fertilizers',
                'type' => 'variable',
                'description' => 'NPK, Urea, TSP, organic fertilizers',
                'is_predictable' => true,
                'typical_percentage' => 18.0
            ],
            [
                'name' => 'Pesticides/Fungicides',
                'type' => 'variable',
                'description' => 'Weedicides, insecticides, fungicides',
                'is_predictable' => true,
                'typical_percentage' => 6.0
            ],
            [
                'name' => 'Fuel/Diesel',
                'type' => 'variable',
                'description' => 'Fuel for machinery and transportation',
                'is_predictable' => true,
                'typical_percentage' => 8.0
            ],
            [
                'name' => 'Seasonal Labor',
                'type' => 'variable',
                'description' => 'Planting, weeding, harvesting labor',
                'is_predictable' => true,
                'typical_percentage' => 15.0
            ],
            [
                'name' => 'Irrigation/Water',
                'type' => 'variable',
                'description' => 'Water charges, pump operation costs',
                'is_predictable' => true,
                'typical_percentage' => 4.0
            ],
            [
                'name' => 'Transportation',
                'type' => 'variable',
                'description' => 'Transport to market, input delivery',
                'is_predictable' => true,
                'typical_percentage' => 5.0
            ],
            [
                'name' => 'Processing/Storage',
                'type' => 'variable',
                'description' => 'Drying, milling, storage costs',
                ' ' => true,
                'typical_percentage' => 4.0
            ],
        ];

        foreach ($categories as $category) {
            CostCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
