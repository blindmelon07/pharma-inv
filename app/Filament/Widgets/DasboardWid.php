<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DasboardWid extends BaseWidget
{
    protected function getStats(): array
    {
        $expiringSoonCount = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->count();

        // Low stock
        $lowStockCount = Product::where('quantity', '<', 10)->count();

        // Fast-moving products (sold more than 20 times in last 30 days)
        $fastMovingCount = Transaction::where('type', 'out')
            ->where('transaction_date', '>=', Carbon::now()->subDays(30))
            ->select('product_id')
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) > 20')
            ->get()
            ->count();

        // Total products
        $totalProducts = Product::count();
        return [
            Stat::make('Total Transactions', Transaction::count()),
            Stat::make('Total Sales', Transaction::where('type', 'out')->sum('quantity')),
            Stat::make('Total Purchases', Transaction::where('type', 'in')->sum('quantity')),
            Stat::make('Total Revenue', Transaction::where('type', 'out')->sum('quantity') * Product::first()->price),
            Stat::make('Expiring Soon', $expiringSoonCount)
                ->description('Products expiring within 30 days')
                ->color($expiringSoonCount > 0 ? 'danger' : 'success')
                ->icon($expiringSoonCount > 0 ? 'heroicon-o-exclamation-circle' : null),
            Stat::make('Low Stock', $lowStockCount)
                ->description('Products with less than 10 units in stock')
                ->color($lowStockCount > 0 ? 'danger' : 'success')
                ->icon($lowStockCount > 0 ? 'heroicon-o-exclamation-circle' : null),
            Stat::make('Fast-Moving Products', $fastMovingCount)
                ->description('Products sold more than 20 times in last 30 days')
                ->color($fastMovingCount > 0 ? 'danger' : 'success')
                ->icon($fastMovingCount > 0 ? 'heroicon-o-exclamation-circle' : null),
            Stat::make('Total Products', $totalProducts)
                ->description('Total number of products in the database')
                ->color($totalProducts > 0 ? 'success' : 'danger')
                ->icon($totalProducts > 0 ? 'heroicon-o-check-circle' : null)

        ];
    }
}