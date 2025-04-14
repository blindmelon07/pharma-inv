<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class LowStockProducts extends StatsOverviewWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan  = 1;
    protected function getCards(): array
    {
        $lowStockCount = Product::where('quantity', '<', value: 10)->count(); // Threshold for low stock

        return [
            Card::make('Low Stock Products', $lowStockCount)
                ->description('Products with low inventory')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),
        ];
    }
}
