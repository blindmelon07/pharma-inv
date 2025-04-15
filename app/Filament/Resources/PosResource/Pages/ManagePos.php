<?php

namespace App\Filament\Resources\PosResource\Pages;

use App\Filament\Resources\PosResource;
use Filament\Resources\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Product;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;

class ManagePos extends Page
{
    protected static string $resource = PosResource::class;
    protected static string $view = 'filament.resources.pos-resource.pages.manage-pos';

    public $cart = [];
    public $totalAmount = 0;
    public $selectedProducts = [];
    public $amountPaid = 0;
    public $change = 0;

    public function mount()
    {
        $this->cart = [];
        $this->totalAmount = 0;
        $this->amountPaid = 0;
        $this->change = 0;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('selectedProducts')
                ->label('Select Products')
                ->options(Product::pluck('name', 'id'))
                ->multiple()
                ->searchable()
                ->live()
                ->afterStateUpdated(fn($state) => $this->addToCart($state)),
        ]);
    }

    public function addToCart($productIds)
    {
        foreach ($productIds as $productId) {
            if (!$productId) continue;

            $product = Product::find($productId);
            if (!$product) continue;

            $existingIndex = collect($this->cart)->search(fn($item) => $item['product_id'] == $product->id);

            if ($existingIndex !== false) {
                $this->cart[$existingIndex]['quantity']++;
                $this->cart[$existingIndex]['total'] = $this->cart[$existingIndex]['quantity'] * $this->cart[$existingIndex]['price'];
            } else {
                $this->cart[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => 1,
                    'total' => $product->price,
                ];
            }
        }

        $this->updateTotalAmount();
        $this->selectedProducts = [];
    }

    public function updateCart()
    {
        foreach ($this->cart as $index => $item) {
            $this->cart[$index]['total'] = $item['quantity'] * $item['price'];
        }

        $this->updateTotalAmount();
    }

    public function updateChange()
    {
        $this->change = floatval($this->amountPaid) - floatval($this->totalAmount);
    }

    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->updateTotalAmount();
    }

    public function updateTotalAmount()
    {
        $this->totalAmount = collect($this->cart)->sum(fn($item) => $item['total']);
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('No items in cart!')
                ->danger()
                ->send();
            return;
        }

        foreach ($this->cart as $item) {
            $product = Product::find($item['product_id']);

            if (!$product) {
                Notification::make()
                    ->title("Product not found: {$item['name']}")
                    ->danger()
                    ->send();
                continue;
            }

            if ($product->quantity < $item['quantity']) {
                Notification::make()
                    ->title("Not enough stock for {$product->name}. Available: {$product->quantity}")
                    ->danger()
                    ->send();
                continue;
            }

            $product->decrement('quantity', $item['quantity']);

            Transaction::create([
                'product_id' => $item['product_id'],
                'type' => 'out',
                'quantity' => $item['quantity'],
                'transaction_date' => now(),
                'amount_paid' => floatval($this->amountPaid),
                'change_amount' => floatval($this->change),
                'notes' => 'Sold via POS checkout',
            ]);
        }

        // Tax calculation
        $taxRate = 0.12; // 12%
        $subtotal = $this->totalAmount / (1 + $taxRate);
        $tax = $this->totalAmount - $subtotal;

        // Image for receipt
        $imagePath = public_path('images/pr1.png');
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
        $imageSrc = 'data:image/' . $imageType . ';base64,' . $imageData;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.receipt', [
            'cart' => $this->cart,
            'amountPaid' => $this->amountPaid,
            'change' => $this->change,
            'logo' => $imageSrc,
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'total' => number_format($this->totalAmount, 2),
        ]);

        $fileName = 'receipt_' . now()->format('Ymd_His') . '.pdf';

        // Reset POS data
        $this->cart = [];
        $this->totalAmount = 0;
        $this->amountPaid = 0;
        $this->change = 0;

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);
    }
}
