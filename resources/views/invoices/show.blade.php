<x-app-layout>
    <div class="space-y-6" x-data="{
        showSentModal: false,
        showPaidModal: false,
        sentDate: '{{ now()->format('Y-m-d') }}',
        paidDate: '{{ now()->format('Y-m-d') }}'
    }">
        
            {{-- Header --}}
            <div class="mb-6">
                {{-- Success Message --}}
                @if(session('success'))
                <div class="mb-4 p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
                    {{ session('success') }}
                </div>
                @endif

                <div class="flex justify-between items-start mb-4">
                    <div>
                        <a href="{{ route('invoices.index') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-500 inline-flex items-center gap-1 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Terug naar overzicht
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Factuur {{ $invoice->invoice_number }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Aangemaakt op {{ $invoice->created_at->format('d-m-Y H:i') }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        {{-- Email (placeholder) --}}
                        <button class="text-white bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Email Versturen
                        </button>

                        {{-- PDF Download --}}
                        <a href="{{ route('invoices.pdf', $invoice) }}" class="text-white bg-gradient-to-r from-purple-500 to-purple-700 hover:from-purple-600 hover:to-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            Download PDF
                        </a>

                        {{-- Print --}}
                        <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="text-white bg-gradient-to-r from-gray-500 to-gray-700 hover:from-gray-600 hover:to-gray-800 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Afdrukken
                        </a>

                        {{-- Edit --}}
                        <a href="{{ route('invoices.edit', $invoice) }}" class="text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Bewerken
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left Column - Invoice Details --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Status & Dates Card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</label>
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        'sent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'cancelled' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Concept',
                                        'sent' => 'Verzonden',
                                        'paid' => 'Betaald',
                                        'overdue' => 'Verlopen',
                                        'cancelled' => 'Geannuleerd'
                                    ];
                                @endphp
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$invoice->status] ?? '' }}">
                                        {{ $statusLabels[$invoice->status] ?? ucfirst($invoice->status) }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Factuurdatum</label>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->invoice_date->format('d-m-Y') }}</p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Vervaldatum</label>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->due_date->format('d-m-Y') }}</p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Betalingstermijn</label>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->payment_terms }} dagen</p>
                            </div>
                        </div>
                    </div>

                    {{-- Customer Info Card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Klantgegevens</h3>
                        <div class="space-y-2">
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Naam:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $invoice->customer->name }}</span>
                            </p>
                            @if($invoice->customer->company_name)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Bedrijf:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $invoice->customer->company_name }}</span>
                            </p>
                            @endif
                            @if($invoice->customer->email)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Email:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $invoice->customer->email }}</span>
                            </p>
                            @endif
                            @if($invoice->customer->phone)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Telefoon:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $invoice->customer->phone }}</span>
                            </p>
                            @endif
                            @if($invoice->customer->vat_number)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">BTW nummer:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $invoice->customer->vat_number }}</span>
                            </p>
                            @endif
                            @if($invoice->customer->address || $invoice->customer->city)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Adres:</span>
                                <span class="text-gray-900 dark:text-white ml-2">
                                    {{ $invoice->customer->address }}
                                    @if($invoice->customer->postal_code || $invoice->customer->city)
                                        <br>{{ $invoice->customer->postal_code }} {{ $invoice->customer->city }}
                                    @endif
                                    @if($invoice->customer->country)
                                        <br>{{ $invoice->customer->country }}
                                    @endif
                                </span>
                            </p>
                            @endif
                        </div>
                    </div>

                    {{-- Invoice Lines --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Factuurregels</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left">Omschrijving</th>
                                        <th scope="col" class="px-6 py-3 text-center">Aantal</th>
                                        <th scope="col" class="px-6 py-3 text-right">Prijs</th>
                                        <th scope="col" class="px-6 py-3 text-center">BTW</th>
                                        <th scope="col" class="px-6 py-3 text-right">Totaal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->lines as $line)
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                            {{ $line->description }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">
                                            {{ number_format($line->quantity, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-gray-700 dark:text-gray-300">
                                            € {{ number_format($line->unit_price, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">
                                            {{ $line->vat_rate }}%
                                        </td>
                                        <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                                            € {{ number_format($line->quantity * $line->unit_price, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($invoice->notes)
                    {{-- Notes --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Opmerkingen</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $invoice->notes }}</p>
                    </div>
                    @endif
                </div>

                {{-- Right Column - Summary --}}
                <div class="space-y-6">
                    {{-- Totals Card --}}
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 rounded-lg shadow-sm border border-blue-200 dark:border-gray-600 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Totalen</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">Subtotaal (excl. BTW)</span>
                                <span class="font-medium text-gray-900 dark:text-white">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">BTW</span>
                                <span class="font-medium text-gray-900 dark:text-white">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="border-t-2 border-blue-300 dark:border-gray-600 pt-3">
                                <div class="flex justify-between">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">Totaal (incl. BTW)</span>
                                    <span class="text-xl font-bold text-blue-700 dark:text-blue-400">€ {{ number_format($invoice->total, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acties</h3>
                        <div class="space-y-2">
                            @if($invoice->status !== 'sent' && $invoice->status !== 'paid')
                            <button @click="showSentModal = true" class="w-full text-left px-4 py-3 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 rounded-lg transition-colors">
                                Markeer als Verzonden
                            </button>
                            @endif
                            @if($invoice->status !== 'paid')
                            <button @click="showPaidModal = true" class="w-full text-left px-4 py-3 text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800 rounded-lg transition-colors">
                                Markeer als Betaald
                            </button>
                            @endif
                            <form action="{{ route('invoices.duplicate', $invoice) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                    Dupliceer Factuur
                                </button>
                            </form>
                            <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" 
                                onsubmit="return confirm('Weet je zeker dat je deze factuur wilt verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 rounded-lg transition-colors">
                                    Verwijder Factuur
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        {{-- Markeer als Verzonden Modal --}}
        <div x-show="showSentModal" 
            x-cloak
            @click.self="showSentModal = false"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Markeer als Verzonden
                    </h3>
                    <button @click="showSentModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('invoices.mark-sent', $invoice) }}" method="POST" class="p-4">
                    @csrf
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Verzenddatum
                        </label>
                        <input type="date" name="sent_date" x-model="sentDate" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showSentModal = false"
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Annuleren
                        </button>
                        <button type="submit"
                            class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Markeer als Betaald Modal --}}
        <div x-show="showPaidModal" 
            x-cloak
            @click.self="showPaidModal = false"
            class="fixed inset-0 bg-gray-900 bg-opacity-50 dark:bg-opacity-80 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Markeer als Betaald
                    </h3>
                    <button @click="showPaidModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <form action="{{ route('invoices.mark-paid', $invoice) }}" method="POST" class="p-4">
                    @csrf
                    <div class="mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Betaaldatum
                        </label>
                        <input type="date" name="paid_date" x-model="paidDate" required
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showPaidModal = false"
                            class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-200 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                            Annuleren
                        </button>
                        <button type="submit"
                            class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5">
                            Opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
    </div>
</x-app-layout>
