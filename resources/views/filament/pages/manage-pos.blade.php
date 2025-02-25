<x-filament::page>
    <x-filament::form wire:submit.prevent="checkout">
        {{ $this->form }}

        <x-filament::button type="submit">
            Checkout
        </x-filament::button>
    </x-filament::form>

    <!-- Display Cart Table -->
    <div class="mt-6">
        <h2 class="text-lg font-bold">Cart</h2>
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 border">Product</th>
                    <th class="px-4 py-2 border">Price</th>
                    <th class="px-4 py-2 border">Quantity</th>
                    <th class="px-4 py-2 border">Total</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cart as $index => $item)
                    <tr>
                        <td class="px-4 py-2 border">{{ $item['name'] }}</td>
                        <td class="px-4 py-2 border">{{ number_format($item['price'], 2) }}</td>
                        <td class="px-4 py-2 border">
                            <input type="number" wire:model="cart.{{ $index }}.quantity" class="w-16 text-center border rounded" min="1">
                        </td>
                        <td class="px-4 py-2 border">{{ number_format($item['total'], 2) }}</td>
                        <td class="px-4 py-2 border">
                            <x-filament::button wire:click="removeFromCart({{ $index }})" color="danger">Remove</x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <h3 class="text-lg font-bold">Total: ₱{{ number_format($totalAmount, 2) }}</h3>
    </div>
</x-filament::page>
