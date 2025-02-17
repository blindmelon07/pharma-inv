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
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
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