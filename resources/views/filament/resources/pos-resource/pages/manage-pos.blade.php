<x-filament::page>
    <!-- Product Selection Form -->
    <form wire:submit.prevent="checkout" class="space-y-4">
        {{ $this->form }}

        <x-filament::button type="submit">
            Checkout
        </x-filament::button>
    </form>

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
                    <th class="px-4 py-2 border">Prescription</th>
                    <th class="px-4 py-2 border">Remarks</th>
                    <th class="px-4 py-2 border">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cart as $index => $item)
                    <tr>
                        <td class="px-4 py-2 border">{{ $item['name'] }}</td>
                        <td class="px-4 py-2 border">{{ number_format($item['price'], 2) }}</td>
                        <td class="px-4 py-2 border">
                            <input type="number" wire:model="cart.{{ $index }}.quantity"
                                   class="w-16 text-center border rounded" min="1" wire:change="updateCart">
                        </td>
                        <td class="px-4 py-2 border">{{ number_format($item['total'], 2) }}</td>
                        <td class="px-4 py-2 border">
                            <select wire:model="cart.{{ $index }}.prescription" class="w-full text-center border rounded">
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </td>
                        <td class="px-4 py-2 border">
                            <input type="text" wire:model="cart.{{ $index }}.remarks"
                                   class="w-full text-center border rounded" placeholder="Enter remarks">
                        </td>
                        <td class="px-4 py-2 border">
                            <x-filament::button wire:click="removeFromCart({{ $index }})" color="danger">
                                Remove
                            </x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </tbody>

            <!-- Display Amount Paid and Change Below the Table -->

        </table>
        <tfoot>
            <tr>
                <td colspan="3" class="px-4 py-2 font-bold text-right border">Total Amount:</td>
                <td class="px-4 py-2 font-bold border">{{ number_format($totalAmount, 2) }}</td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="3" class="px-4 py-2 font-bold text-right border">Amount Paid:</td>
                <td class="px-4 py-2 border">
                    <input type="number" wire:model="amountPaid" wire:input="updateChange"
                           class="w-full text-center border rounded" min="0">
                </td>
                <td colspan="3"></td>
            </tr>
            <tr>
                <td colspan="3" class="px-4 py-2 font-bold text-right border">Change:</td>
                <td class="px-4 py-2 font-bold text-green-600 border">{{ number_format($change, 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </div>


</x-filament::page>
