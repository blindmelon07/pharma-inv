<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\PosResource;
use App\Filament\Resources\TransactionResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;
    public function afterCreate(): void
    {
        Notification::make('pos-created')
        ->title('Product Created')
        ->body('Your product has been recorded successfully.')
        ->sendToDatabase(Auth::user());
    }
}