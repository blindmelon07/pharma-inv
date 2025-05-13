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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        // Prepare the query for the selected period
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

        // Get the transactions for the given period
        $transactions = $query->get();

        // Calculate total sales
        $totalSales = $transactions->sum(function ($transaction) {
            return $transaction->quantity * $transaction->product->price;
        });

        // Create a new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add BOM (Byte Order Mark) to fix encoding issues in Excel
        // This ensures that Excel will correctly handle the UTF-8 encoding
        // fwrite($file, "\xEF\xBB\xBF"); // Uncomment if you're writing directly to CSV

        // Add the logo to the spreadsheet
        $logoPath = public_path('images/pr.png'); // Update with the correct path to your logo file
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Company Logo');
            $drawing->setDescription('Company Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(100); // Adjust logo size
            $drawing->setCoordinates('A1'); // Position the logo in the first row, first column
            $drawing->setWorksheet($sheet);
        }

        // Add the headers for the transaction data
        $sheet->setCellValue('A6', 'Product');
        $sheet->setCellValue('B6', 'Type');
        $sheet->setCellValue('C6', 'Quantity');
        $sheet->setCellValue('D6', 'Price');
        $sheet->setCellValue('E6', 'Total Revenue');
        $sheet->setCellValue('F6', 'Transaction Date');

        // Populate the rows with transaction data, starting from row 7
        $row = 7; // Start from row 7 (to leave space for the logo and headers)
        foreach ($transactions as $transaction) {
            $totalRevenue = $transaction->quantity * $transaction->product->price;

            $sheet->setCellValue('A' . $row, $transaction->product->name);
            $sheet->setCellValue('B' . $row, $transaction->type);
            $sheet->setCellValue('C' . $row, $transaction->quantity);
            $sheet->setCellValue('D' . $row, $transaction->product->price);
            $sheet->setCellValue('E' . $row, $totalRevenue);
            $sheet->setCellValue('F' . $row, $transaction->transaction_date);
            $row++;
        }

        // Add a row for the total sales at the bottom
        $sheet->setCellValue('D' . $row, 'Total Sales');
        $sheet->setCellValue('E' . $row, '₱' . number_format($totalSales, 2));
        // Write the response to the stream
        $writer = new Xlsx($spreadsheet);

        // Set the filename for the Excel file
        $fileName = "sales_report_{$period}_{$now->format('Y-m-d')}.xlsx";

        // Set the headers for downloading the Excel file
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, $headers);
    }
}
