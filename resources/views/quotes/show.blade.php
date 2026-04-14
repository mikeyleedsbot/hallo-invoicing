<x-app-layout>
    <div class="space-y-6" x-data="{
        showSentModal: false,
        sentDate: '{{ now()->format('Y-m-d') }}'
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
                        <a href="{{ route('quotes.index') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-500 inline-flex items-center gap-1 mb-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Terug naar overzicht
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Offerte {{ $quote->quote_number }}
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Aangemaakt op {{ $quote->created_at->format('d-m-Y H:i') }}
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
                        <a href="{{ route('quotes.pdf', $quote) }}" class="text-white bg-gradient-to-r from-purple-500 to-purple-700 hover:from-purple-600 hover:to-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                            </svg>
                            Download PDF
                        </a>

                        {{-- Print --}}
                        <a href="{{ route('quotes.print', $quote) }}" target="_blank" class="text-white bg-gradient-to-r from-gray-500 to-gray-700 hover:from-gray-600 hover:to-gray-800 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            Afdrukken
                        </a>

                        {{-- Convert to Invoice (only if accepted and not already converted) --}}
                        @if($quote->status === 'accepted' && !$quote->converted_invoice_id)
                        <form action="{{ route('quotes.convert', $quote) }}" method="POST" onsubmit="return confirm('Offerte omzetten naar factuur? Deze actie kan niet ongedaan worden gemaakt.');">
                            @csrf
                            <button type="submit" class="text-white bg-gradient-to-r from-amber-500 to-amber-700 hover:from-amber-600 hover:to-amber-800 focus:ring-4 focus:ring-amber-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                Omzetten naar Factuur
                            </button>
                        </form>
                        @elseif($quote->converted_invoice_id)
                        <a href="{{ route('invoices.show', $quote->converted_invoice_id) }}" class="text-white bg-gradient-to-r from-green-500 to-green-700 hover:from-green-600 hover:to-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Bekijk Factuur
                        </a>
                        @endif

                        {{-- Edit --}}
                        <a href="{{ route('quotes.edit', $quote) }}" class="text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2.5 inline-flex items-center gap-2">
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
                                        'accepted' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'expired' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300'
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Concept',
                                        'sent' => 'Verzonden',
                                        'accepted' => 'Geaccepteerd',
                                        'rejected' => 'Afgewezen',
                                        'expired' => 'Verlopen'
                                    ];
                                @endphp
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$quote->status] ?? '' }}">
                                        {{ $statusLabels[$quote->status] ?? ucfirst($quote->status) }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Offertedatum</label>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $quote->quote_date->format('d-m-Y') }}</p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Geldig tot</label>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $quote->valid_until->format('d-m-Y') }}</p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Geldigheid</label>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $quote->valid_days }} dagen</p>
                            </div>
                        </div>
                    </div>

                    {{-- Customer Info Card --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Klantgegevens</h3>
                        <div class="space-y-2">
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Naam:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $quote->customer->name }}</span>
                            </p>
                            @if($quote->customer->company_name)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Bedrijf:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $quote->customer->company_name }}</span>
                            </p>
                            @endif
                            @if($quote->customer->email)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Email:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $quote->customer->email }}</span>
                            </p>
                            @endif
                            @if($quote->customer->phone)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Telefoon:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $quote->customer->phone }}</span>
                            </p>
                            @endif
                            @if($quote->customer->vat_number)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">BTW nummer:</span>
                                <span class="text-gray-900 dark:text-white ml-2">{{ $quote->customer->vat_number }}</span>
                            </p>
                            @endif
                            @if($quote->customer->address || $quote->customer->city)
                            <p class="text-sm">
                                <span class="font-medium text-gray-500 dark:text-gray-400">Adres:</span>
                                <span class="text-gray-900 dark:text-white ml-2">
                                    {{ $quote->customer->address }}
                                    @if($quote->customer->postal_code || $quote->customer->city)
                                        <br>{{ $quote->customer->postal_code }} {{ $quote->customer->city }}
                                    @endif
                                    @if($quote->customer->country)
                                        <br>{{ $quote->customer->country }}
                                    @endif
                                </span>
                            </p>
                            @endif
                        </div>
                    </div>

                    {{-- Invoice Lines --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Offerteregels</h3>
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
                                    @foreach($quote->lines as $line)
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

                    @if($quote->notes)
                    {{-- Notes --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Opmerkingen</h3>
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $quote->notes }}</p>
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
                                <span class="font-medium text-gray-900 dark:text-white">€ {{ number_format($quote->subtotal, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-700 dark:text-gray-300">BTW</span>
                                <span class="font-medium text-gray-900 dark:text-white">€ {{ number_format($quote->vat_amount, 2, ',', '.') }}</span>
                            </div>
                            <div class="border-t-2 border-blue-300 dark:border-gray-600 pt-3">
                                <div class="flex justify-between">
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">Totaal (incl. BTW)</span>
                                    <span class="text-xl font-bold text-blue-700 dark:text-blue-400">€ {{ number_format($quote->total, 2, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Acties</h3>
                        <div class="space-y-2">
                            @if($quote->status !== 'sent' && !$quote->converted_invoice_id)
                            <button @click="showSentModal = true" class="w-full text-left px-4 py-3 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 rounded-lg transition-colors">
                                Markeer als Verzonden
                            </button>
                            @endif
                            
                            @if(!$quote->converted_invoice_id)
                            <form action="{{ route('quotes.convert', $quote) }}" method="POST" onsubmit="return confirm('Offerte omzetten naar factuur?');">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 dark:bg-amber-900 dark:text-amber-300 dark:hover:bg-amber-800 rounded-lg transition-colors">
                                    Dupliceer naar Factuur
                                </button>
                            </form>
                            @endif
                            
                            <button class="w-full text-left px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Dupliceer Offerte
                            </button>
                            <form action="{{ route('quotes.destroy', $quote) }}" method="POST" 
                                onsubmit="return confirm('Weet je zeker dat je deze offerte wilt verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-3 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 rounded-lg transition-colors">
                                    Verwijder Offerte
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
                <form action="{{ route('quotes.mark-sent', $quote) }}" method="POST" class="p-4">
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
        </div>
    </div>
</x-app-layout>
