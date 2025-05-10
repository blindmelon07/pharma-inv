<?php

namespace App\Observers;

use App\Filament\Resources\PosResource;
use App\Models\Transaction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PosObserver
{
    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {

        Notification::make()
            ->title('POS Transaction Created')
            ->body('Your transaction has been recorded successfully.')
            ->icon('heroicon-o-check-circle')
            ->actions([
                Action::make('View')
                    ->url(PosResource::getUrl('index'))
                    ->button(),
            ])
            ->sendToDatabase(Auth::user()); // Save to the current user's notifications

    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "deleted" event.
     */
    public function deleted(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "restored" event.
     */
    public function restored(Transaction $transaction): void
    {
        //
    }

    /**
     * Handle the Transaction "force deleted" event.
     */
    public function forceDeleted(Transaction $transaction): void
    {
        //
    }
}