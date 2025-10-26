<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostCategory extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'cost_categories';

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_predictable',
        'typical_percentage'
    ];

    protected $casts = [
        'is_predictable' => 'boolean',
        'typical_percentage' => 'decimal:2'
    ];

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:cost_categories,name',
            'type' => 'required|in:fixed,variable',
            'description' => 'nullable|string',
            'is_predictable' => 'boolean',
            'typical_percentage' => 'nullable|numeric|min:0|max:100'
        ];
    }

    public function costs(): HasMany
    {
        return $this->hasMany(FarmingCost::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(CostPrediction::class);
    }

    // Business logic
    public function getAverageCostAttribute(): float
    {
        return $this->costs()->avg('amount') ?? 0;
    }

    public function getTotalHistoricalCostsAttribute(): float
    {
        return $this->costs()->sum('amount');
    }

    // Scopes
    public function scopePredictable($query)
    {
        return $query->where('is_predictable', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
