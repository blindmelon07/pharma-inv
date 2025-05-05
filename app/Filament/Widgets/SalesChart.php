<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Overview';
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;
    public function getHeading(): string
    {
        return 'Sales Overview - Total Revenue: ₱' . number_format($this->getTotalSalesRevenue(), 2);
    }

    private function getTotalSalesRevenue(): float
    {
        return Transaction::where('type', 'out') // Only sales transactions
            ->join('products', 'transactions.product_id', '=', 'products.id') // Join with products table
            ->selectRaw('SUM(transactions.quantity * products.price) as total_revenue') // Calculate total revenue
            ->value('total_revenue') ?? 0; // If null, return 0
    }

    protected function getData(): array
    {
        // Get daily sales revenue for the last 7 days
        $dailySales = Transaction::where('type', 'out') // Only sales transactions
            ->where('transaction_date', '>=', Carbon::now()->subDays(7)) // Last 7 days
            ->join('products', 'transactions.product_id', '=', 'products.id') // Join with products table
            ->select(
                DB::raw('DATE(transaction_date) as date'),
                DB::raw('SUM(transactions.quantity * products.price) as total_revenue')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Get weekly sales revenue for the last 4 weeks
        $weeklySales = Transaction::where('type', 'out') // Only sales transactions
            ->where('transaction_date', '>=', Carbon::now()->subWeeks(4)) // Last 4 weeks
            ->join('products', 'transactions.product_id', '=', 'products.id') // Join with products table
            ->select(
                DB::raw('YEARWEEK(transaction_date) as week'),
                DB::raw('SUM(transactions.quantity * products.price) as total_revenue')
            )
            ->groupBy('week')
            ->orderBy('week', 'asc')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Daily Revenue (Last 7 Days)',
                    'data' => $dailySales->pluck('total_revenue')->toArray(),
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Weekly Revenue (Last 4 Weeks)',
                    'data' => $weeklySales->pluck('total_revenue')->toArray(),
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => array_merge(
                $dailySales->pluck('date')->toArray(),
                $weeklySales->pluck('week')->toArray()
            ),
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Use a line chart
    }
}