<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please seed products first.');
            return;
        }

        // Generate transactions for the last 60 days
        foreach (range(1, 200) as $i) {
            $product = $products->random();
            $type = rand(0, 1) ? 'out' : 'in';
            $quantity = rand(1, 15);
            $price = $product->price;
            $date = Carbon::now()->subDays(rand(0, 59))->toDateString();

            if ($type === 'out') {
                $amount_paid = $price * $quantity + rand(0, 50);
                $change_amount = $amount_paid - ($price * $quantity);
            } else {
                $amount_paid = 0;
                $change_amount = 0;
            }

            Transaction::create([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'notes' => $type === 'out' ? 'Customer purchase' : 'Stock replenishment',
                'transaction_date' => $date,
                'prescription' => rand(0, 1) ? 'Yes' : 'No',
                'remarks' => $type === 'out' ? 'Sold at counter' : 'Received from supplier',
                'amount_paid' => $amount_paid,
                'change_amount' => $change_amount,
            ]);
        }
    }
}