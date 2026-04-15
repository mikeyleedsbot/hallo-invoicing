<x-app-layout>
    <div class="space-y-6" x-data="invoiceForm()">
        {{-- Header --}}
        @if(session('success'))
        <div class="mb-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
            {{ session('success') }}
        </div>
        @endif

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Factuur Bewerken
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Bewerk factuur {{ $invoice->invoice_number }}
                </p>
            </div>
            <a href="{{ route('invoices.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>

        <form action="{{ route('invoices.update', $invoice) }}" method="POST" @submit="submitForm" class="space-y-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" value="{{ $invoice->status }}">
            
            {{-- Summary Card (Top) --}}
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 rounded-lg shadow-sm border border-blue-200 dark:border-gray-600 p-6">
                <div class="grid grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Subtotaal</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(subtotal)">€ 0,00</div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">BTW</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatCurrency(vatAmount)">€ 0,00</div>
                    </div>
                    <div class="text-center">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Totaal</div>
                        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="formatCurrency(total)">€ 0,00</div>
                    </div>
                </div>
            </div>

            {{-- Invoice Details --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Factuurgegevens</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Factuurnummer <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="invoice_number" value="{{ $invoice->invoice_number }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Klant <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" required x-tom-select="{placeholder: 'Zoek klant...', maxOptions: null}"
                            x-model="customerId"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Selecteer klant...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ $invoice->customer_id == $customer->id ? 'selected' : '' }}>{{ $customer->name }}@if($customer->company_name) ({{ $customer->company_name }})@endif</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Factuurdatum <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Vervaldatum <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Template
                        </label>
                        <select name="template_id" x-tom-select="{placeholder: 'Selecteer template...'}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Standaard template</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" {{ $invoice->template_id == $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}@if($template->is_default) (standaard)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Invoice Lines (Table Layout) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Factuurregels</h2>
                    <div class="flex gap-2">
                        <button type="button" @click="showProductModal = true"
                            class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Product toevoegen
                        </button>
                        <button type="button" @click="addLine()"
                            class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Regel toevoegen
                        </button>
                    </div>
                </div>

                {{-- Product Modal --}}
                <div x-show="showProductModal" x-cloak
                    class="fixed inset-0 z-50 flex items-center justify-center backdrop-blur-sm bg-black/50"
                    @keydown.escape.window="showProductModal = false">
                    <div class="relative w-full max-w-2xl mx-4 bg-white rounded-xl shadow-2xl dark:bg-gray-800" @click.outside="showProductModal = false">
                        <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Product toevoegen</h3>
                            <button type="button" @click="showProductModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <input type="text" x-model="productSearch" placeholder="Zoek product..."
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                                @keydown.escape.stop="showProductModal = false">
                        </div>
                        <div class="p-4 space-y-2 max-h-96 overflow-y-auto">
                            @foreach($products->where('active', true) as $product)
                            <button type="button"
                                x-show="productSearch === '' || '{{ strtolower($product->name . ' ' . $product->description) }}'.includes(productSearch.toLowerCase())"
                                @click="addProduct({{ json_encode(['description' => $product->name, 'unit_price' => (float)$product->price, 'quantity' => 1]) }})"
                                class="w-full text-left flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-blue-50 dark:hover:bg-gray-700 hover:border-blue-300 transition-colors">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $product->name }}</div>
                                    @if($product->description)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $product->description }}</div>
                                    @endif
                                </div>
                                <div class="text-sm font-semibold text-blue-600 dark:text-blue-400 ml-4 whitespace-nowrap">
                                    € {{ number_format($product->price, 2, ',', '.') }}
                                </div>
                            </button>
                            @endforeach
                            <p x-show="!hasVisibleProducts()" class="text-gray-500 dark:text-gray-400 text-sm text-center py-4">Geen producten gevonden.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">
                                <th class="text-left pb-2">Omschrijving</th>
                                <th class="text-left pb-2 w-28">Aantal</th>
                                <th class="text-left pb-2 w-32">Prijs/stuk</th>
                                <th class="text-left pb-2 w-24">BTW%</th>
                                <th class="text-center pb-2 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="space-y-2">
                            <template x-for="(line, index) in lines" :key="index">
                                <tr>
                                    <td colspan="5" class="pt-2">
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50 dark:bg-gray-700">
                                            <table class="w-full">
                                                <tr>
                                                    <td class="pr-3">
                                                        <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description" required
                                                            placeholder="Bijv: Website ontwikkeling" 
                                                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400">
                                                    </td>
                                                    <td class="pr-3 w-28">
                                                        <input type="number" :name="'lines[' + index + '][quantity]'" x-model="line.quantity" required
                                                            step="0.01" min="0.01" placeholder="1"
                                                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400">
                                                    </td>
                                                    <td class="pr-3 w-32">
                                                        <div class="relative">
                                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                                <span class="text-gray-500 dark:text-gray-400 text-xs">€</span>
                                                            </div>
                                                            <input type="number" :name="'lines[' + index + '][unit_price]'" x-model="line.unit_price" required
                                                                step="0.01" min="0" placeholder="0.00"
                                                                class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400">
                                                        </div>
                                                    </td>
                                                    <td class="pr-3 w-24">
                                                        <select :name="'lines[' + index + '][vat_rate]'" x-model="line.vat_rate" required
                                                            :disabled="vatReverseCharged"
                                                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white disabled:opacity-60 disabled:cursor-not-allowed">
                                                            @foreach($vatRates as $vat)
                                                                <option value="{{ (int)$vat->rate }}">{{ number_format($vat->rate, 0) }}%</option>
                                                            @endforeach
                                                            <option value="0" x-show="vatReverseCharged">0%</option>
                                                        </select>
                                                    </td>
                                                    <td class="w-12 text-center">
                                                        <button type="button" @click="removeLine(index)" :disabled="lines.length === 1"
                                                            class="p-2.5 text-red-600 hover:text-white hover:bg-red-600 disabled:text-gray-400 disabled:hover:bg-transparent disabled:cursor-not-allowed border border-red-600 disabled:border-gray-300 dark:border-red-500 dark:disabled:border-gray-600 rounded-lg transition-colors inline-flex items-center justify-center"
                                                            :title="lines.length === 1 ? 'Minimaal 1 regel vereist' : 'Verwijder regel'">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </table>
                                            <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 text-right">
                                                <span class="text-xs text-gray-600 dark:text-gray-400">Regeltotaal: </span>
                                                <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="formatCurrency(lineTotal(line))">€ 0,00</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- BTW verlegd --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="vat_reverse_charged" value="1" x-model="vatReverseCharged"
                        @change="toggleReverseCharge($event)"
                        class="mt-1 w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                    <div class="flex-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">BTW verlegd</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Alle regels worden automatisch op 0% gezet en er wordt een opmerking toegevoegd met het BTW-nummer van de klant.
                        </p>
                        <div x-show="vatReverseCharged && customerVatNumber()" x-cloak
                            class="mt-3 p-3 rounded-md bg-amber-50 border border-amber-200 dark:bg-amber-900/30 dark:border-amber-800">
                            <p class="text-xs text-amber-900 dark:text-amber-100">
                                BTW wordt verlegd naar BTW-nummer: <strong x-text="customerVatNumber()"></strong>
                            </p>
                        </div>
                        <div x-show="vatReverseCharged && !customerVatNumber()" x-cloak
                            class="mt-3 p-3 rounded-md bg-red-50 border border-red-200 dark:bg-red-900/30 dark:border-red-800">
                            <p class="text-xs text-red-900 dark:text-red-100">
                                <strong>Let op:</strong> deze klant heeft geen BTW-nummer. Vul eerst een BTW-nummer in op de klantkaart voordat je BTW kunt verleggen.
                            </p>
                        </div>
                    </div>
                </label>
            </div>

            {{-- Notes --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Opmerkingen</label>
                <textarea name="notes" rows="3" placeholder="Extra opmerkingen of voorwaarden..."
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">{{ $invoice->notes }}</textarea>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('invoices.index') }}"
                    class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                    Annuleren
                </a>
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Wijzigingen opslaan
                </button>
            </div>
        </form>
    </div>

    <script>
        const invoiceLines = @json($invoice->lines);
        
        function invoiceForm() {
            return {
                showProductModal: false,
                productSearch: '',
                customerId: '{{ $invoice->customer_id }}',
                vatReverseCharged: {{ $invoice->vat_reverse_charged ? 'true' : 'false' }},
                customers: @json($customers->pluck('vat_number', 'id')),
                lines: invoiceLines.map(line => ({
                    description: line.description,
                    quantity: parseFloat(line.quantity),
                    unit_price: parseFloat(line.unit_price),
                    vat_rate: parseFloat(line.vat_rate)
                })),

                customerVatNumber() {
                    if (!this.customerId) return '';
                    const v = this.customers[this.customerId];
                    return v ? String(v).trim() : '';
                },

                toggleReverseCharge(event) {
                    if (this.vatReverseCharged) {
                        if (!this.customerVatNumber()) {
                            alert('Deze klant heeft geen BTW-nummer. Vul eerst een BTW-nummer in op de klantkaart.');
                            this.vatReverseCharged = false;
                            return;
                        }
                        this.lines.forEach(l => l.vat_rate = 0);
                    }
                },

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
                        vat_rate: this.vatReverseCharged ? 0 : {{ $defaultVat }}
                    });
                },

                addProduct(product) {
                    this.lines.push({
                        description: product.description,
                        quantity: product.quantity,
                        unit_price: product.unit_price,
                        vat_rate: this.vatReverseCharged ? 0 : {{ $defaultVat }}
                    });
                    this.showProductModal = false;
                    this.productSearch = '';
                },

                hasVisibleProducts() {
                    if (this.productSearch === '') return true;
                    const q = this.productSearch.toLowerCase();
                    return @json(collect($products->where('active', true))->map(fn($p) => strtolower($p->name . ' ' . $p->description))->values()).some(s => s.includes(q));
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
</x-app-layout>
