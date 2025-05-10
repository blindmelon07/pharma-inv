<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Sales Reports';
    protected static ?string $modelLabel = 'Sales Report';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // No form needed for reports
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_usage')
                    ->label('Average Daily Usage (%)')
                    ->getStateUsing(function ($record) {
                        // Calculate average usage based on transaction data
                        $totalUsage = Transaction::where('product_id', $record->product_id)
                            ->where('type', 'out') // Only consider 'out' type (sales or dispensation)
                            ->whereBetween('transaction_date', [
                                Carbon::now()->subDays(30)->toDateString(), // Last 30 days for example
                                Carbon::now()->toDateString(),
                            ])
                            ->sum('quantity');

                        return $totalUsage / 30; // Average per day in last 30 days
                    })
                    ->formatStateUsing(fn($state) => number_format($state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('period')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                    ])
                    ->default('daily')
                    ->query(function ($query, $data) {
                        $period = $data['value'] ?? 'daily';
                        $now = Carbon::now();

                        switch ($period) {
                            case 'daily':
                                $query->whereDate('transaction_date', $now->toDateString());
                                break;
                            case 'weekly':
                                $query->whereBetween('transaction_date', [
                                    $now->startOfWeek()->toDateString(),
                                    $now->endOfWeek()->toDateString(),
                                ]);
                                break;
                            case 'monthly':
                                $query->whereBetween('transaction_date', [
                                    $now->startOfMonth()->toDateString(),
                                    $now->endOfMonth()->toDateString(),
                                ]);
                                break;
                        }
                    }),
                SelectFilter::make('type')
                    ->label('Transaction Type')
                    ->options([
                        'in' => 'Stock In',
                        'out' => 'Stock Out',
                    ])
                    ->query(fn($query, $data) => $query->where('type', $data['value'])),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name'),
                Tables\Filters\Filter::make('custom_date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('to')->label('To'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['from']) {
                            $query->whereDate('transaction_date', '>=', $data['from']);
                        }

                        if ($data['to']) {
                            $query->whereDate('transaction_date', '<=', $data['to']);
                        }
                    }),
                SelectFilter::make('movement')
                    ->label('Item Movement')
                    ->options([
                        'fast' => 'Fast-Moving Items',
                        'slow' => 'Slow-Moving Items',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'fast') {
                            // Fetch fast-moving items by summing quantity sold and sorting by highest sales
                            $query->join(DB::raw('(
                                SELECT product_id, SUM(quantity) as total_quantity
                                FROM transactions
                                WHERE type = "out"
                                GROUP BY product_id
                                ORDER BY total_quantity DESC
                                LIMIT 10
                            ) as movement'), 'transactions.product_id', '=', 'movement.product_id');
                        }

                        if ($data['value'] === 'slow') {
                            // Fetch slow-moving items by summing quantity sold and sorting by lowest sales
                            $query->join(DB::raw('(
                                SELECT product_id, SUM(quantity) as total_quantity
                                FROM transactions
                                WHERE type = "out"
                                GROUP BY product_id
                                ORDER BY total_quantity ASC
                                LIMIT 10
                            ) as movement'), 'transactions.product_id', '=', 'movement.product_id');
                        }
                    }),
            ])

            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->form([
                        Forms\Components\Select::make('period')
                            ->options([
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('daily')
                            ->required(),
                    ])
                    ->action(function ($data) {
                        return self::downloadReport($data);
                    }),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),

        ];
    }

    protected static function downloadReport($data): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $period = $data['period'] ?? 'daily';
        $now = Carbon::now();

        $query = Transaction::query()
            ->where('type', 'out') // Only include outgoing transactions
            ->with('product'); // Eager load the product relationship

        switch ($period) {
            case 'daily':
                $query->whereDate('transaction_date', $now->toDateString());
                break;
            case 'weekly':
                $query->whereBetween('transaction_date', [
                    $now->startOfWeek()->toDateString(),
                    $now->endOfWeek()->toDateString(),
                ]);
                break;
            case 'monthly':
                $query->whereBetween('transaction_date', [
                    $now->startOfMonth()->toDateString(),
                    $now->endOfMonth()->toDateString(),
                ]);
                break;
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            throw new \Exception("No transactions found for the selected period: {$period}");
        }

        $fileName = "sales_report_{$period}_{$now->format('Y-m-d')}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Product',
                'Type',
                'Quantity',
                'Price',
                'Total Revenue',
                'Transaction Date',
            ]);

            // Add rows
            foreach ($transactions as $transaction) {
                $totalRevenue = $transaction->quantity * $transaction->product->price;

                fputcsv($file, [
                    $transaction->product->name,
                    $transaction->type,
                    $transaction->quantity,
                    $transaction->product->price,
                    $totalRevenue,
                    $transaction->transaction_date,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}