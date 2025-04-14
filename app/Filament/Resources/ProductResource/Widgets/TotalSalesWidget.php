<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use Filament\Forms\Components\Card;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TotalSalesWidget extends ChartWidget
{
    protected static ?string $heading = 'Total Sales';
    protected static ?int $sort = 3;
    protected function getData(): array
    {
        // Fetch product names and their total sales (price * quantity)
        $products = Product::select('name')
            ->selectRaw('quantity * price as total_sales')
            ->orderByDesc('total_sales')
            ->limit(10) // Show top 10 products
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Sales',
                    'data' => $products->pluck('total_sales')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $products->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bubble';
    }
}
