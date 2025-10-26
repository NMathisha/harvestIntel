<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FarmingCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'farming_operation_id',
        'cost_category_id',
        'description',
        'amount',
        'incurred_date',
        'quantity',
        'unit',
        'unit_price',
        'external_factors'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'incurred_date' => 'date',
        'external_factors' => 'array'
    ];

    public static function rules(): array
    {
        return [
            'farming_operation_id' => 'required|exists:farming_operations,id',
            'cost_category_id' => 'required|exists:cost_categories,id',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'incurred_date' => 'required|date',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:50',
            'unit_price' => 'nullable|numeric|min:0',
            'external_factors' => 'nullable|array'
        ];
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(FarmingOperation::class, 'farming_operation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    // Business logic
    public function calculateUnitPrice(): ?float
    {
        if ($this->quantity && $this->quantity > 0) {
            return $this->amount / $this->quantity;
        }
        return $this->unit_price;
    }

    public function getCostPerAcreAttribute(): float
    {
        return $this->operation && $this->operation->total_acres > 0
            ? $this->amount / $this->operation->total_acres
            : 0;
    }
}
