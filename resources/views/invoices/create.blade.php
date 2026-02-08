<x-app-layout>
    <div class="space-y-6">
        
            {{-- Header --}}
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Nieuwe Factuur
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Maak een nieuwe factuur aan voor een klant
                        </p>
                    </div>
                    <a href="{{ route('invoices.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <form action="{{ route('invoices.store') }}" method="POST" x-data="invoiceForm()" @submit.prevent="submitForm">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Main Form --}}
                    <div class="lg:col-span-2 space-y-6">
                        {{-- Invoice Details Card --}}
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Factuurgegevens</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Invoice Number --}}
                                <div>
                                    <label for="invoice_number" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Factuurnummer <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="invoice_number" id="invoice_number" value="{{ $invoiceNumber }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                        required>
                                    @error('invoice_number')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Customer Select with Search --}}
                                <div x-data="{ open: false, search: '', selected: null }">
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Klant <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <button @click="open = !open" type="button"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-left flex justify-between items-center">
                                            <span x-text="selected ? selected.name : 'Selecteer klant...'" class="truncate"></span>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        
                                        <input type="hidden" name="customer_id" :value="selected?.id" required>
                                        
                                        {{-- Dropdown --}}
                                        <div x-show="open" @click.away="open = false"
                                            class="absolute z-10 w-full mt-1 bg-white rounded-lg shadow-lg dark:bg-gray-700 border border-gray-200 dark:border-gray-600">
                                            <div class="p-2">
                                                <input type="text" x-model="search" placeholder="Zoek klant..."
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                            </div>
                                            <ul class="max-h-60 overflow-auto py-1">
                                                @foreach($customers as $customer)
                                                <li>
                                                    <button type="button" @click="selected = {{ $customer->toJson() }}; open = false"
                                                        x-show="'{{ strtolower($customer->name) }}'.includes(search.toLowerCase())"
                                                        class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-sm text-gray-700 dark:text-gray-200">
                                                        <div class="font-medium">{{ $customer->name }}</div>
                                                        @if($customer->company_name)
                                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->company_name }}</div>
                                                        @endif
                                                    </button>
                                                </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    @error('customer_id')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Invoice Date --}}
                                <div>
                                    <label for="invoice_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Factuurdatum <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="invoice_date" id="invoice_date" value="{{ now()->format('Y-m-d') }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        required>
                                    @error('invoice_date')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Due Date --}}
                                <div>
                                    <label for="due_date" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Vervaldatum <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="due_date" id="due_date" value="{{ now()->addDays(14)->format('Y-m-d') }}"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        required>
                                    @error('due_date')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Payment Terms --}}
                                <div class="md:col-span-2">
                                    <label for="payment_terms" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                        Betalingstermijn (dagen)
                                    </label>
                                    <select name="payment_terms" id="payment_terms"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                        <option value="14" selected>14 dagen</option>
                                        <option value="30">30 dagen</option>
                                        <option value="60">60 dagen</option>
                                        <option value="90">90 dagen</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Invoice Lines Card --}}
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Factuurregels</h2>
                                <button type="button" @click="addLine"
                                    class="text-blue-700 hover:text-white border border-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-3 py-2 dark:border-blue-500 dark:text-blue-500 dark:hover:text-white dark:hover:bg-blue-500 inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Regel toevoegen
                                </button>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(line, index) in lines" :key="index">
                                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                                        <div class="flex justify-between items-start">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="`Regel ${index + 1}`"></span>
                                            <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                                class="text-red-600 hover:text-red-800 dark:text-red-500">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-12 gap-3">
                                            {{-- Description --}}
                                            <div class="col-span-12 md:col-span-6">
                                                <input type="text" :name="`lines[${index}][description]`" x-model="line.description" placeholder="Omschrijving"
                                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                                    required>
                                            </div>

                                            {{-- Quantity --}}
                                            <div class="col-span-4 md:col-span-2">
                                                <input type="number" :name="`lines[${index}][quantity]`" x-model="line.quantity" placeholder="Aantal" step="0.01" min="0.01"
                                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                                    required>
                                            </div>

                                            {{-- Unit Price --}}
                                            <div class="col-span-4 md:col-span-2">
                                                <input type="number" :name="`lines[${index}][unit_price]`" x-model="line.unit_price" placeholder="Prijs" step="0.01" min="0"
                                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                                    required>
                                            </div>

                                            {{-- VAT Rate --}}
                                            <div class="col-span-4 md:col-span-2">
                                                <select :name="`lines[${index}][vat_rate]`" x-model="line.vat_rate"
                                                    class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white"
                                                    required>
                                                    <option value="0">0%</option>
                                                    <option value="9">9%</option>
                                                    <option value="21" selected>21%</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Line Total --}}
                                        <div class="text-right">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                Totaal: <strong x-text="formatCurrency(lineTotal(line))"></strong>
                                            </span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                            <label for="notes" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Opmerkingen
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Extra opmerkingen of voorwaarden..."></textarea>
                        </div>
                    </div>

                    {{-- Summary Sidebar --}}
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-20">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Overzicht</h2>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">Subtotaal:</span>
                                    <strong class="text-gray-900 dark:text-white" x-text="formatCurrency(subtotal)"></strong>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600 dark:text-gray-400">BTW:</span>
                                    <strong class="text-gray-900 dark:text-white" x-text="formatCurrency(vatAmount)"></strong>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                                    <div class="flex justify-between">
                                        <span class="text-base font-semibold text-gray-900 dark:text-white">Totaal:</span>
                                        <strong class="text-lg text-blue-600 dark:text-blue-500" x-text="formatCurrency(total)"></strong>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <button type="submit"
                                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                                    Factuur opslaan
                                </button>
                                <a href="{{ route('invoices.index') }}"
                                    class="w-full text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 block text-center">
                                    Annuleren
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function invoiceForm() {
            return {
                lines: [{
                    description: '',
                    quantity: 1,
                    unit_price: 0,
                    vat_rate: 21
                }],

                get subtotal() {
                    return this.lines.reduce((sum, line) => {
                        return sum + this.lineTotal(line);
                    }, 0);
                },

                get vatAmount() {
                    return this.lines.reduce((sum, line) => {
                        const lineTotal = this.lineTotal(line);
                        return sum + (lineTotal * (parseFloat(line.vat_rate) / 100));
                    }, 0);
                },

                get total() {
                    return this.subtotal + this.vatAmount;
                },

                lineTotal(line) {
                    return parseFloat(line.quantity || 0) * parseFloat(line.unit_price || 0);
                },

                formatCurrency(amount) {
                    return '€ ' + parseFloat(amount || 0).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                },

                addLine() {
                    this.lines.push({
                        description: '',
                        quantity: 1,
                        unit_price: 0,
                        vat_rate: 21
                    });
                },

                removeLine(index) {
                    if (this.lines.length > 1) {
                        this.lines.splice(index, 1);
                    }
                },

                submitForm(event) {
                    event.target.submit();
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
