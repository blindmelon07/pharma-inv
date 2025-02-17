<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
        $table->string('name');
        $table->unsignedBigInteger('category_id')->constrained()->onDelete('cascade');
        $table->unsignedBigInteger('supplier_id')->constrained()->onDelete('cascade');
        $table->integer('quantity');
        $table->decimal('price', 8, 2);
        $table->date('expiry_date')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};