<?php

namespace App\Filament\Resources\PosResource\Pages;

use App\Filament\Resources\PosResource;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePos extends CreateRecord
{
    protected static string $resource = PosResource::class;
    public function afterCreate(): void
    {
        Notification::make('pos-created')
        ->title('Product Created')
        ->body('Your product has been recorded successfully.')
        ->sendToDatabase(Auth::user());
    }
}