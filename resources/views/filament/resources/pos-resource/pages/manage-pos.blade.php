<x-filament::page>
    <div class="max-w-full px-8 mx-auto">
        <form wire:submit.prevent="checkout" class="space-y-8">
            <!-- Header Section -->
            <div class="w-full p-10 bg-white rounded-lg shadow-sm">
                <h2 class="mb-8 text-3xl font-bold text-gray-800">Point of Sale</h2>
                {{ $this->form }}
            </div>

            <!-- Cart Section -->
            <div class="p-6 bg-white rounded-lg shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Shopping Cart</h2>
                    <span class="text-sm text-gray-500">Items: {{ count($cart) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Total</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Prescription</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Remarks</th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($cart as $index => $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">{{ $item['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">₱{{ number_format($item['price'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="number" wire:model="cart.{{ $index }}.quantity"
                                               class="w-20 text-center border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                               min="1" wire:change="updateCart">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">₱{{ number_format($item['total'], 2) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <select wire:model="cart.{{ $index }}.prescription"
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500">
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="text" wire:model="cart.{{ $index }}.remarks"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                               placeholder="Enter remarks">
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                        <x-filament::button wire:click="removeFromCart({{ $index }})"
                                                          color="danger"
                                                          size="sm">
                                            Remove
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Payment Summary -->
                <div class="p-6 mt-6 rounded-lg bg-gray-50">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Total Amount:</span>
                                <span class="text-lg font-bold text-gray-900">₱{{ number_format($totalAmount, 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Amount Paid:</span>
                                <div class="w-48">
                                    <input type="number"
                                           wire:model="amountPaid"
                                           wire:input="updateChange"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                           min="0"
                                           placeholder="Enter amount">
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600">Change:</span>
                                <span class="text-lg font-bold text-green-600">₱{{ number_format($change, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checkout Button -->
            <div class="flex justify-center">
                <x-filament::button type="submit"
                                  size="lg"
                                  class="px-8 py-3">
                    Complete Checkout
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament::page>
