<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        //     'name' => fake()->word(),
        // 'category_id' => Category::factory(),
        // 'supplier_id' => Supplier::factory(),
        // 'quantity' => fake()->numberBetween(0, 1000),
        // 'price' => fake()->randomFloat(2, 1, 1000),
        // 'expiry_date' => fake()->dateBetween('now', '+2 years'),
        ];
    }
}