<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Product::factory()->create([
            'name' => fake()->word(),
            'category_id' => Category::factory(),
            'supplier_id' => Supplier::factory(),
            'quantity' => fake()->numberBetween(0, 1000),
            'price' => fake()->randomFloat(2, 1, 1000),
            'expiry_date' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
        ]);


    }
}