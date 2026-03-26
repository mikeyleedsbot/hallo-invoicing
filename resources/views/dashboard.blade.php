<x-app-layout>
    <div class="space-y-6">
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Welkom terug, {{ Auth::user()->name }}! 👋</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Hier is een overzicht van je facturatie activiteiten.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Openstaand -->
            <div class="relative overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>

                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Openstaand</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">€ {{ number_format($openstaandBedrag, 2, ',', '.') }}</p>
                    <p class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ $openstaandAantal }} {{ $openstaandAantal === 1 ? 'factuur' : 'facturen' }}</span>
                        <span class="mx-2">•</span>
                        <span>onbetaald</span>
                    </p>
                </div>
            </div>

            <!-- Deze Maand -->
            <div class="relative overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>

                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Deze Maand</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">€ {{ number_format($dezeMaandBedrag, 2, ',', '.') }}</p>
                    <p class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ $dezeMaandAantal }} {{ $dezeMaandAantal === 1 ? 'factuur' : 'facturen' }}</span>
                        <span class="mx-2">•</span>
                        <span>{{ now()->isoFormat('MMMM') }}</span>
                    </p>
                </div>
            </div>

            <!-- Klanten -->
            <div class="relative overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    @if($klantenDezeMaand > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        +{{ $klantenDezeMaand }} nieuw
                    </span>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Klanten</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $klantenAantal }}</p>
                    <p class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                        <span>Actieve relaties</span>
                    </p>
                </div>
            </div>

            <!-- Offertes -->
            <div class="relative overflow-hidden bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    @if($offertesSent > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                        {{ $offertesSent }} verstuurd
                    </span>
                    @endif
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Offertes</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ $offertesAantal }}</p>
                    <p class="mt-2 flex items-center text-sm text-gray-600 dark:text-gray-400">
                        @if($offertesDraft > 0)
                            <span>{{ $offertesDraft }} concept{{ $offertesDraft > 1 ? 'en' : '' }}</span>
                            @if($offertesSent > 0)<span class="mx-2">•</span>@endif
                        @endif
                        @if($offertesSent > 0)
                            <span>{{ $offertesSent }} wacht op goedkeuring</span>
                        @elseif($offertesAantal === 0)
                            <span>Nog geen offertes</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                </svg>
                Snelle Acties
            </h2>
            <div class="grid grid-cols-3 gap-4">
                <a href="{{ route('invoices.create') }}" class="group relative flex items-center gap-4 px-6 py-5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-xl transition-all shadow-sm hover:shadow-md">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-white/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="font-semibold block">Nieuwe Factuur</span>
                        <span class="text-xs text-blue-100">Maak een factuur aan</span>
                    </div>
                    <svg class="w-5 h-5 ml-auto transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
                <a href="{{ route('quotes.create') }}" class="group relative flex items-center gap-4 px-6 py-5 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-xl transition-all shadow-sm hover:shadow-md">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="font-semibold block">Nieuwe Offerte</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">Stuur een offerte</span>
                    </div>
                    <svg class="w-5 h-5 ml-auto text-gray-400 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
                <a href="{{ route('customers.index') }}" class="group relative flex items-center gap-4 px-6 py-5 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-xl transition-all shadow-sm hover:shadow-md">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <div>
                        <span class="font-semibold block">Klanten</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $klantenAantal }} klanten beheren</span>
                    </div>
                    <svg class="w-5 h-5 ml-auto text-gray-400 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Recent Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Recente Facturen
                    </h2>
                    <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 flex items-center gap-1">
                        Bekijk alle
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3">Factuurnummer</th>
                            <th scope="col" class="px-6 py-3">Klant</th>
                            <th scope="col" class="px-6 py-3">Bedrag</th>
                            <th scope="col" class="px-6 py-3">Datum</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3"><span class="sr-only">Acties</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recenteFacturen as $factuur)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $factuur->invoice_number }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $factuur->customer->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                € {{ number_format($factuur->total, 2, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $factuur->invoice_date->format('d-m-Y') }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'draft'     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        'sent'      => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'paid'      => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'overdue'   => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'cancelled' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                    ];
                                    $statusLabels = [
                                        'draft'     => 'Concept',
                                        'sent'      => 'Verstuurd',
                                        'paid'      => 'Betaald',
                                        'overdue'   => 'Verlopen',
                                        'cancelled' => 'Geannuleerd',
                                    ];
                                    $color = $statusColors[$factuur->status] ?? 'bg-gray-100 text-gray-800';
                                    $label = $statusLabels[$factuur->status] ?? ucfirst($factuur->status);
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('invoices.show', $factuur) }}" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-xs">
                                    Bekijken →
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nog geen facturen</h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">Start met het aanmaken van je eerste factuur om hier activiteit te zien.</p>
                                    <a href="{{ route('invoices.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Nieuwe Factuur
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
