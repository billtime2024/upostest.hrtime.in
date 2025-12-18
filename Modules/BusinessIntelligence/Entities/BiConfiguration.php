<?php

namespace Modules\BusinessIntelligence\Entities;

use Illuminate\Database\Eloquent\Model;

class BiConfiguration extends Model
{
    protected $table = 'bi_configurations';

    protected $fillable = [
        'business_id',
        'config_key',
        'config_value',
        'config_type',
        'category',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the config value properly typed
     */
    public function getTypedValue()
    {
        switch ($this->config_type) {
            case 'boolean':
                return filter_var($this->config_value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $this->config_value;
            case 'json':
            case 'array':
                return json_decode($this->config_value, true);
            default:
                return $this->config_value;
        }
    }

    /**
     * Set config value with automatic type handling
     */
    public function setTypedValue($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->config_type = 'json';
            $this->config_value = json_encode($value);
        } elseif (is_bool($value)) {
            $this->config_type = 'boolean';
            $this->config_value = $value ? '1' : '0';
        } elseif (is_numeric($value) && !is_string($value)) {
            $this->config_type = 'integer';
            $this->config_value = (string) $value;
        } else {
            $this->config_type = 'string';
            $this->config_value = $value;
        }
    }

    /**
     * Scope for active configurations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}

