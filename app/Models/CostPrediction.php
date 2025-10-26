<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CostPrediction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farming_operation_id',
        'cost_category_id',
        'predicted_amount',
        'confidence_score',
        'prediction_factors',
        'model_used',
        'prediction_date',
        'target_date',
        'actual_amount',
        'prediction_error'
    ];

    // protected $casts = [
    //     'predicted_amount' => 'decimal:2',
    //     'actual_amount' => 'decimal:2',
    //     'confidence_score' => 'decimal:4',
    //     'prediction_error' => 'decimal:4',
    //     'prediction_factors' => 'array',
    //     'prediction_date' => 'datetime',
    //     'target_date' => 'date'
    // ];

    public function operation(): BelongsTo
    {
        return $this->belongsTo(FarmingOperation::class, 'farming_operation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    // Business logic
    public function calculateError(): ?float
    {
        if ($this->actual_amount && $this->actual_amount > 0) {
            return abs($this->predicted_amount - $this->actual_amount) / $this->actual_amount;
        }
        return null;
    }

    public function updateActualAmount(float $actualAmount): void
    {
        $this->update([
            'actual_amount' => $actualAmount,
            'prediction_error' => $this->calculateError()
        ]);
    }

    // Scopes
    public function scopeWithActuals($query)
    {
        return $query->whereNotNull('actual_amount');
    }

    public function scopeAccurate($query, float $threshold = 0.2)
    {
        return $query->where('prediction_error', '<=', $threshold);
    }
}
