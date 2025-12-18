<?php

namespace Modules\BusinessIntelligence\Entities;

use Illuminate\Database\Eloquent\Model;

class BiPrediction extends Model
{
    protected $table = 'bi_predictions';

    protected $fillable = [
        'business_id',
        'prediction_type',
        'target_entity_type',
        'target_entity_id',
        'prediction_date',
        'predicted_values',
        'confidence_intervals',
        'accuracy_score',
        'actual_values',
        'model_used',
        'model_parameters',
        'training_data_summary',
        'predicted_at',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'prediction_date' => 'date',
        'predicted_values' => 'array',
        'confidence_intervals' => 'array',
        'actual_values' => 'array',
        'model_parameters' => 'array',
        'training_data_summary' => 'array',
        'accuracy_score' => 'decimal:2',
        'predicted_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    protected $dates = [
        'prediction_date',
        'predicted_at',
        'validated_at',
    ];

    /**
     * Scope by prediction type
     */
    public function scopeType($query, $type)
    {
        return $query->where('prediction_type', $type);
    }

    /**
     * Scope for future predictions
     */
    public function scopeFuture($query)
    {
        return $query->where('prediction_date', '>=', now()->toDateString());
    }

    /**
     * Scope for past predictions
     */
    public function scopePast($query)
    {
        return $query->where('prediction_date', '<', now()->toDateString());
    }

    /**
     * Scope for validated predictions
     */
    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    /**
     * Check if prediction can be validated
     */
    public function canValidate()
    {
        return $this->prediction_date->isPast() && !$this->validated_at;
    }

    /**
     * Validate prediction with actual values
     */
    public function validate(array $actualValues)
    {
        $this->actual_values = $actualValues;
        $this->validated_at = now();
        
        // Calculate accuracy if possible
        if (isset($actualValues['value']) && isset($this->predicted_values['value'])) {
            $predicted = $this->predicted_values['value'];
            $actual = $actualValues['value'];
            $error = abs($predicted - $actual) / $actual;
            $this->accuracy_score = max(0, (1 - $error) * 100);
        }
        
        $this->save();
    }

    /**
     * Get accuracy as formatted percentage
     */
    public function getFormattedAccuracyAttribute()
    {
        return $this->accuracy_score ? number_format($this->accuracy_score, 1) . '%' : 'N/A';
    }
}

