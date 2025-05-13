<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    protected function getTableRecordClasses()
    {
        return function ($record) {
            $isLowStock = $record->quantity < 10;

            $isExpiringSoon = $record->expiry_date &&
                $record->expiry_date >= now() &&
                $record->expiry_date <= now()->addDays(30);

            $isFastMoving = Transaction::where('product_id', $record->id)
                ->where('type', 'out')
                ->where('transaction_date', '>=', now()->subDays(30))
                ->sum('quantity') > 20;

            if ($isLowStock || $isExpiringSoon || $isFastMoving) {
                return 'bg-red-100 text-red-800'; // Tailwind classes for row color
            }

            return ''; // Default row (no color)
        };
    }
}
