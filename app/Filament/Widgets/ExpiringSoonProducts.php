<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class ExpiringSoonProducts extends StatsOverviewWidget
{
    protected static ?int $sort = 0; // Adjust position in dashboard
    protected function getCards(): array
    {
        $expiringSoonCount = Product::whereNotNull('expiry_date')
            ->where('expiry_date', '>=', Carbon::now())
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->count();

        return [
            Card::make('Expiring Soon Products', $expiringSoonCount)
                ->description('Products expiring within 30 days')
                ->color($expiringSoonCount > 0 ? 'warning' : 'success'),
        ];

    }
    protected int|string|array $columnSpan = [
        'md' => 1, // Make it take half the width on medium+ screens
    ];
}