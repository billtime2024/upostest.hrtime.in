<?php

namespace Modules\Scheme\Database\factories;

use Modules\Scheme\Entities\Scheme;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchemeFactory extends Factory
{
    protected $model = Scheme::class;

    public function definition()
    {
        return [
            'business_id' => $this->faker->numberBetween(1, 10),
            'scheme_name' => $this->faker->word,
            'scheme_amount' => $this->faker->randomFloat(2, 10, 1000),
            'scheme_type' => $this->faker->randomElement(['fixed', 'percentage']),
            'enable_slab' => false,
            'slab_calculation_type' => null,
            'supplier_id' => null,
            'product_id' => null,
            'starts_at' => now()->subDays(10),
            'ends_at' => now()->addDays(10),
            'scheme_note' => $this->faker->sentence,
            'created_by' => 1,
        ];
    }
}