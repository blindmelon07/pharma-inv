<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'notes',
        'transaction_date',
    ];

    protected static function booted()
    {
        static::creating(function ($transaction) {
            $product = $transaction->product;

            if ($transaction->type === 'out') {
                // Decrement product quantity for "out" transactions
                if ($product->quantity < $transaction->quantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }
                $product->decrement('quantity', $transaction->quantity);
            } elseif ($transaction->type === 'in') {
                // Increment product quantity for "in" transactions
                $product->increment('quantity', $transaction->quantity);
            }
        });

        static::updating(function ($transaction) {
            $product = $transaction->product;
            $originalQuantity = $transaction->getOriginal('quantity');
            $newQuantity = $transaction->quantity;

            if ($transaction->type === 'out') {
                // Handle updates to "out" transactions
                $difference = $newQuantity - $originalQuantity;

                if ($product->quantity + $originalQuantity < $newQuantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $product->decrement('quantity', $difference);
            } elseif ($transaction->type === 'in') {
                // Handle updates to "in" transactions
                $difference = $newQuantity - $originalQuantity;
                $product->increment('quantity', $difference);
            }
        });

        static::deleting(function ($transaction) {
            $product = $transaction->product;

            if ($transaction->type === 'out') {
                // Restore product quantity for deleted "out" transactions
                $product->increment('quantity', $transaction->quantity);
            } elseif ($transaction->type === 'in') {
                // Decrement product quantity for deleted "in" transactions
                $product->decrement('quantity', $transaction->quantity);
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}