<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PosResource\Pages;
use App\Models\Product;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class PosResource extends Resource
{
    protected static ?string $model = Product::class; // Use Product model for search

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'POS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Cart Items (Repeater)
                Repeater::make('cart')
                    ->schema([
                        TextInput::make('name')
                            ->label('Product')
                            ->disabled(),
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->disabled(),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->default(1)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $index = $get('__index');
                                $price = $get("cart.{$index}.price");
                                $set("cart.{$index}.total", $price * $state);

                                // Update total amount dynamically
                                $totalAmount = collect($get('cart'))->sum('total');
                                $set('totalAmount', $totalAmount);
                            }),
                        TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->disabled(),
                    ])
                    ->live()
                    ->columns(4),

                // Total Amount (Auto-Calculated)
                TextInput::make('totalAmount')
                    ->label('Total Amount')
                    ->numeric()
                    ->disabled()
                    ->default(0)
                    ->live()
                    ->afterStateHydrated(function (Set $set, Get $get) {
                        $totalAmount = collect($get('cart'))->sum('total');
                        $set('totalAmount', $totalAmount);
                    }),

                // Amount Paid
                TextInput::make('amount_paid')
                    ->label('Amount Paid')
                    ->numeric()
                    ->live()
                    ->default(0)
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $totalAmount = $get('totalAmount') ?? 0;
                        $change = max(0, $state - $totalAmount);
                        $set('change', $change);
                    }),

                // Change (Auto-Calculated)
                TextInput::make('change')
                    ->label('Change')
                    ->numeric()
                    ->disabled()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->sortable(),
                Tables\Columns\TextColumn::make('change_amount')
                    ->label('Change')
                    ->sortable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ListPos::route('/'),
            'index' => Pages\ManagePos::route('/'),
            'create' => Pages\CreatePos::route('/create'),
            'edit' => Pages\EditPos::route('/{record}/edit'),
        ];
    }
}