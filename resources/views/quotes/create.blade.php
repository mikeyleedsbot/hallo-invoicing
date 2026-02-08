<x-app-layout>
    <div class="space-y-6" x-data="quoteForm()">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Nieuwe Offerte
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Maak een nieuwe offerte aan voor een klant
                </p>
            </div>
            <a href="{{ route('quotes.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </a>
        </div>

        <form action="{{ route('quotes.store') }}" method="POST" @submit="submitForm">
            @csrf
            
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
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Offertegegevens</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Invoice Number --}}
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Offertenummer <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="quote_number" value="{{ $quoteNumber }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Customer --}}
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Klant <span class="text-red-500">*</span>
                        </label>
                        <select name="customer_id" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option value="">Selecteer klant...</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}@if($customer->company_name) ({{ $customer->company_name }})@endif</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Invoice Date --}}
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Offertedatum <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="quote_date" value="{{ now()->format('Y-m-d') }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Due Date --}}
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Geldig tot <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="valid_until" value="{{ now()->addDays(14)->format('Y-m-d') }}" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- Invoice Lines (Full Width) --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Offerteregels</h2>
                    <button type="button" @click="addLine()" 
                        class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2 inline-flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Regel toevoegen
                    </button>
                </div>

                {{-- Column Headers --}}
                <div class="mb-2">
                    <div class="p-3">
                        <div class="flex gap-2">
                            <div class="flex-1 min-w-0">
                                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Omschrijving</label>
                            </div>
                            <div class="w-28">
                                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Aantal</label>
                            </div>
                            <div class="w-32">
                                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Prijs/stuk</label>
                            </div>
                            <div class="w-24">
                                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">BTW%</label>
                            </div>
                            <div class="w-12">
                                <label class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase text-center block">Actie</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50 dark:bg-gray-700">
                            <div class="flex gap-2 items-center">
                                {{-- Description --}}
                                <div class="flex-1 min-w-0">
                                    <input type="text" :name="'lines[' + index + '][description]'" x-model="line.description" required
                                        placeholder="Bijv: Website ontwikkeling" 
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400">
                                </div>

                                {{-- Quantity --}}
                                <div class="w-28">
                                    <input type="number" :name="'lines[' + index + '][quantity]'" x-model="line.quantity" required
                                        step="0.01" min="0.01" placeholder="1"
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400">
                                </div>

                                {{-- Unit Price --}}
                                <div class="w-32">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <span class="text-gray-500 dark:text-gray-400 text-xs">€</span>
                                        </div>
                                        <input type="number" :name="'lines[' + index + '][unit_price]'" x-model="line.unit_price" required
                                            step="0.01" min="0" placeholder="0.00"
                                            class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-7 p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white dark:placeholder-gray-400">
                                    </div>
                                </div>

                                {{-- VAT Rate --}}
                                <div class="w-24">
                                    <select :name="'lines[' + index + '][vat_rate]'" x-model="line.vat_rate" required
                                        class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                        <option value="0">0%</option>
                                        <option value="9">9%</option>
                                        <option value="21">21%</option>
                                    </select>
                                </div>

                                {{-- Delete Button --}}
                                <div class="w-12 flex items-center justify-center">
                                    <button type="button" @click="removeLine(index)" x-show="lines.length > 1"
                                        class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 dark:text-red-500 dark:hover:text-red-400 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                        title="Verwijder regel">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Line Total --}}
                            <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 text-right">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Regeltotaal: </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="formatCurrency(lineTotal(line))">€ 0,00</span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Opmerkingen</label>
                <textarea name="notes" rows="3" placeholder="Extra opmerkingen of voorwaarden..."
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('quotes.index') }}"
                    class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                    Annuleren
                </a>
                <button type="submit"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700">
                    Offerte opslaan
                </button>
            </div>
        </form>
    </div>

    <script>
        function quoteForm() {
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
</x-app-layout>
