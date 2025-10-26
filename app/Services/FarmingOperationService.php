<?php
namespace App\Services;

use Illuminate\Http\Request;
use App\Models\FarmingOperation;

class FarmingOperationService
{
    public function getFilteredOperations(Request $request)
    {
        $query = FarmingOperation::query();

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            switch ($request->status) {
                case 'active':
                    $query->active();
                    break;
                case 'completed':
                    $query->completed();
                    break;
                case 'planned':
                    $query->where('season_start', '>', now());
                    break;
            }
        }

        if ($request->has('location')) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'season_start');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $operations = $query->paginate($perPage);

        // Transform
        $operations->getCollection()->transform(function ($operation) {
            return [
                'id' => $operation->id,
                'name' => $operation->name,
                'type' => $operation->type,
                'total_acres' => $operation->total_acres,
                'season_start' => $operation->season_start->format('Y-m-d'),
                'season_end' => $operation->season_end->format('Y-m-d'),
                'season_length_days' => $operation->season_length,
                'expected_yield' => $operation->expected_yield,
                'yield_unit' => $operation->yield_unit,
                'commodity_price' => $operation->commodity_price,
                'location' => $operation->location,
                'status' => $operation->isCompleted() ? 'Completed' :
                           ($operation->isActive() ? 'Active' : 'Planned'),
                'total_costs' => $operation->total_costs,
                'cost_per_acre' => $operation->cost_per_acre,
                'created_at' => $operation->created_at,
                'updated_at' => $operation->updated_at
            ];
        });

        return $operations;
    }
}
