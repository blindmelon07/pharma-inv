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
               // Product Search
               TextInput::make('search')
               ->label('Search Products')
               ->placeholder('Enter product name or barcode')
               ->live()
               ->afterStateUpdated(function ($state, Set $set) {
                   $products = Product::where('name', 'like', "%{$state}%")
                       ->orWhere('barcode', 'like', "%{$state}%")
                       ->limit(10)
                       ->get();

                   $set('searchResults', $products);
               }),

           // Display Search Results
           Forms\Components\Grid::make()
               ->schema([
                   Select::make('selectedProduct')
                       ->label('Select Product')
                       ->options(function (Get $get): Collection {
                           return $get('searchResults') ?? collect();
                       })
                       ->searchable()
                       ->live()
                       ->afterStateUpdated(function ($state, Set $set, $get) {
                           if ($state) {
                               $product = Product::find($state);
                               $cart = $get('cart') ?? [];
                               $cart[] = [
                                   'product_id' => $product->id,
                                   'name' => $product->name,
                                   'price' => $product->price,
                                   'quantity' => 1,
                                   'total' => $product->price * 1,
                               ];
                               $set('cart', $cart);
                               $set('selectedProduct', null); // Reset selected product
                           }
                       }),
               ]),

           // Cart
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
                       ->afterStateUpdated(function ($state, Set $set, $get) {
                           $index = $get('__index');
                           $price = $get("cart.{$index}.price");
                           $set("cart.{$index}.total", $price * $state);
                       }),
                   TextInput::make('total')
                       ->label('Total')
                       ->numeric()
                       ->disabled(),
               ])
               ->live()
               ->columns(4),

           // Total
           TextInput::make('totalAmount')
               ->label('Total Amount')
               ->numeric()
               ->disabled()
               ->default(function (Get $get) {
                   return collect($get('cart'))->sum('total');
               }),

           // Checkout Button
           Forms\Components\Actions::make([
               Action::make('checkout')
                   ->label('Checkout')
                   ->action(function (array $data) {
                       // Process the sale and update inventory
                       $cart = $data['cart'];
                       foreach ($cart as $item) {
                           // Create a transaction
                           Transaction::create([
                               'product_id' => $item['product_id'],
                               'type' => 'out',
                               'quantity' => $item['quantity'],
                               'transaction_date' => now(),
                           ]);
                       }

                       // Show success message
                       Notification::make()
                           ->title('Sale processed successfully!')
                           ->success()
                           ->send();
                   }),
           ]),
       ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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