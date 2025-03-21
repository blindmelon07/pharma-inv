<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'delivery_date',
        'status',
        'supplier_id',
        'product_details',
        'quantity',
        'unit',
    ];
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
