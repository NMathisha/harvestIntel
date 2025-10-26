<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class  FarmingOperation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'total_acres',
        'season_start',
        'season_end',
        'expected_yield',
        'yield_unit',
        'weather_data',
        'commodity_price',
        'location'
    ];

    protected $casts = [
        'season_start' => 'date',
        'season_end' => 'date',
        'total_acres' => 'decimal:2',
        'expected_yield' => 'decimal:2',
        'commodity_price' => 'decimal:2',
        'weather_data' => 'array'
    ];

    // Validation rules
    // public static function rules(): array
    // {
    //     return [
    //         'name' => 'required|string|max:255',
    //         'type' => 'required|in:crops,livestock,mixed',
    //         'total_acres' => 'required|numeric|min:0.01',
    //         'season_start' => 'required|date',
    //         'season_end' => 'required|date|after:season_start',
    //         'expected_yield' => 'nullable|numeric|min:0',
    //         'yield_unit' => 'nullable|string|max:50',
    //         'commodity_price' => 'nullable|numeric|min:0',
    //         'location' => 'nullable|string|max:255',
    //         'weather_data' => 'nullable|array'
    //     ];
    // }

    // Relationships
    public function costs(): HasMany
    {
        return $this->hasMany(FarmingCost::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(CostPrediction::class);
    }

    public function fixedCosts(): HasMany
    {
        return $this->costs()->whereHas('category', function ($query) {
            $query->where('type', 'fixed');
        });
    }

    public function variableCosts(): HasMany
    {
        return $this->costs()->whereHas('category', function ($query) {
            $query->where('type', 'variable');
        });
    }

    // Business Logic Methods
    public function getSeasonLengthAttribute(): int
    {
        return $this->season_start->diffInDays($this->season_end);
    }

    public function getTotalCostsAttribute(): float
    {
        return $this->costs()->sum('amount');
    }

    public function getCostPerAcreAttribute(): float
    {
        return $this->total_acres > 0 ? $this->total_costs / $this->total_acres : 0;
    }

    public function isActive(): bool
    {
        $now = Carbon::now();
        return $now->between($this->season_start, $this->season_end);
    }

    public function isCompleted(): bool
    {
        return Carbon::now()->greaterThan($this->season_end);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('season_start', '<=', Carbon::now())
            ->where('season_end', '>=', Carbon::now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('season_end', '<', Carbon::now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
