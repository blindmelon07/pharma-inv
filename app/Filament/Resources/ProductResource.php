<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Inventory Management';
    // Make the resource globally searchable
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'quantity'];
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Quantity' => $record->quantity,
        ];
    }
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('index', ['record' => $record]);
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required(),
                Forms\Components\Select::make('product_type')
                    ->label('Type')
                    ->options([
                        'tablet' => 'Tablet',
                        'capsule' => 'Capsule',
                        'syrup' => 'Syrup',
                        'suspension' => 'Suspension',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('quantity')->numeric()->required(),
                Forms\Components\TextInput::make('quantity_per_box')
                    ->numeric()
                    ->default(1)
                    ->required()
                    ->label('Quantity per Box'),
                Forms\Components\TextInput::make('price')->numeric()->required(),
                Forms\Components\DatePicker::make('expiry_date'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('category.name')->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')->sortable(),
                Tables\Columns\TextColumn::make('product_type')->label('Product Type')->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->color(function ($record) {
                        // Apply red color for low stock
                        return $record->quantity < 10 ? 'danger' : null;
                    }),
                Tables\Columns\TextColumn::make('quantity_per_box')->label('Qty/Box')->sortable(),
                Tables\Columns\TextColumn::make('price')->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->color(function ($record) {
                        // Apply yellow color for expiring soon
                        return ($record->expiry_date && $record->expiry_date <= now()->addDays(30)) ? 'warning' : null;
                    }),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn($query) => $query->where('quantity', '<', 10)),
                Filter::make('fast_moving')
                    ->label('Fast-Moving')
                    ->query(function ($query) {
                        $productIds = Transaction::where('type', 'out')
                            ->where('transaction_date', '>=', now()->subDays(30))
                            ->select('product_id')
                            ->groupBy('product_id')
                            ->havingRaw('SUM(quantity) > 20')
                            ->pluck('product_id');

                        return $query->whereIn('id', $productIds);
                    }),
                Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(function ($query) {
                        return $query
                            ->whereNotNull('expiry_date')
                            ->where('expiry_date', '>=', now())
                            ->where('expiry_date', '<=', now()->addDays(30));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
