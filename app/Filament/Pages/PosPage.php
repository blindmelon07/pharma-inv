<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;

class PosPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string $view = 'filament.pages.pos-page'; // Ensure Blade file exists
    protected static ?string $title = 'Point of Sale';

    public ?array $cart = [];
    public ?float $totalAmount = 0;
    public ?float $amount_paid = 0;
    public ?float $change = 0;

    protected function getFormSchema(): array
    {
        return [
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

            // Total Amount
            TextInput::make('totalAmount')
                ->label('Total Amount')
                ->numeric()
                ->disabled()
                ->default(0)
                ->live()
                ->afterStateUpdated(fn (Set $set, Get $get) => $set('totalAmount', collect($get('cart'))->sum('total'))),

            // Amount Paid
            TextInput::make('amount_paid')
                ->label('Amount Paid')
                ->numeric()
                ->live()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    $totalAmount = $get('totalAmount') ?? 0;
                    $set('change', max(0, $state - $totalAmount));
                }),

            // Change
            TextInput::make('change')
                ->label('Change')
                ->numeric()
                ->disabled()
                ->default(0),
        ];
    }
}