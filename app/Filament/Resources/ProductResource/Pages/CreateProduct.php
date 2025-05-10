<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;

use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    public function afterCreate(): void
    {
        Notification::make('product-created')
        ->title('Product Created')
        ->body('Your product has been recorded successfully.')
        ->sendToDatabase(FacadesAuth::user());
    }
}