<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Inventory Management'; // Group in the sidebar
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('delivery_date')
                ->required(),
            Forms\Components\TextInput::make('status')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('supplier_id')
                ->relationship('supplier', 'name')
                ->required(),
            Forms\Components\Textarea::make('product_details')
                ->required(),
            Forms\Components\TextInput::make('quantity')
                ->required()
                ->numeric(),
            Forms\Components\Select::make('unit')
                ->options([
                    'box' => 'Box',
                    'pcs' => 'Pcs',
                ])
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delivery_date')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('status')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('supplier.name')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('product_details')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('quantity')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('unit')
                ->sortable()
                ->searchable(),
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
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
        ];
    }
}
