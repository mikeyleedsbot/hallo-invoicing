<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Offerte {{ $quote->quote_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                margin: 0;
                padding: 20px;
            }
            
            @page {
                margin: 1cm;
            }
        }
    </style>
</head>
<body class="bg-white">
    <!-- Print Button (hidden when printing) -->
    <div class="no-print fixed top-4 right-4 z-50">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg shadow-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Print Offerte
        </button>
    </div>

    <div class="max-w-4xl mx-auto p-8">
        <!-- Header -->
        <div class="border-b-4 border-blue-600 pb-6 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-blue-900 mb-2">Hallo ICT</h1>
                    <div class="text-sm text-gray-600 space-y-0.5">
                        <p>Reactorweg 301</p>
                        <p>3542 AD Utrecht</p>
                        <p>Nederland</p>
                        <p class="mt-2">KvK: 12345678</p>
                        <p>BTW: NL123456789B01</p>
                        <p>info@hallo.nl</p>
                        <p>+31 (0)30 123 4567</p>
                    </div>
                </div>
                
                <div class="text-right">
                    <h1 class="text-4xl font-bold text-blue-900 mb-2">OFFERTE</h1>
                    <p class="text-xl font-semibold text-gray-600 mb-3">{{ $quote->quote_number }}</p>
                    <span class="inline-block px-4 py-1.5 text-sm font-bold rounded-full
                        @if($quote->status === 'paid') bg-green-100 text-green-800
                        @elseif($quote->status === 'overdue') bg-red-100 text-red-800
                        @elseif($quote->status === 'sent') bg-blue-100 text-blue-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ ucfirst($quote->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Klant</h3>
                <div class="bg-gray-50 border-l-4 border-blue-600 p-4">
                    <p class="font-bold text-gray-900">{{ $quote->customer->name }}</p>
                    @if($quote->customer->company_name)
                        <p class="text-gray-700">{{ $quote->customer->company_name }}</p>
                    @endif
                    @if($quote->customer->address)
                        <p class="text-gray-600 mt-2">{{ $quote->customer->address }}</p>
                    @endif
                    @if($quote->customer->postal_code || $quote->customer->city)
                        <p class="text-gray-600">{{ $quote->customer->postal_code }} {{ $quote->customer->city }}</p>
                    @endif
                    @if($quote->customer->country)
                        <p class="text-gray-600">{{ $quote->customer->country }}</p>
                    @endif
                    @if($quote->customer->vat_number)
                        <p class="text-gray-600 mt-2">BTW: {{ $quote->customer->vat_number }}</p>
                    @endif
                </div>
            </div>
            
            <div>
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-2">Offertedetails</h3>
                <div class="bg-gray-50 border-l-4 border-blue-600 p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-700">Offertedatum:</span>
                        <span class="text-gray-900">{{ $quote->quote_date->format('d-m-Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-700">Geldig tot:</span>
                        <span class="text-gray-900">{{ $quote->valid_until->format('d-m-Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="font-semibold text-gray-700">Geldigheid:</span>
                        <span class="text-gray-900">{{ $quote->valid_days }} dagen</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Lines -->
        <div class="mb-8">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-blue-900 text-white">
                        <th class="text-left p-3 text-xs font-bold uppercase tracking-wide">Omschrijving</th>
                        <th class="text-center p-3 text-xs font-bold uppercase tracking-wide">Aantal</th>
                        <th class="text-right p-3 text-xs font-bold uppercase tracking-wide">Prijs</th>
                        <th class="text-center p-3 text-xs font-bold uppercase tracking-wide">BTW</th>
                        <th class="text-right p-3 text-xs font-bold uppercase tracking-wide">Totaal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quote->lines as $index => $line)
                    <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} border-b border-gray-200">
                        <td class="p-3">
                            <div class="font-semibold text-gray-900">{{ $line->description }}</div>
                        </td>
                        <td class="p-3 text-center text-gray-700">{{ number_format($line->quantity, 0, ',', '.') }}</td>
                        <td class="p-3 text-right text-gray-700">€ {{ number_format($line->unit_price, 2, ',', '.') }}</td>
                        <td class="p-3 text-center text-gray-700">{{ $line->vat_rate }}%</td>
                        <td class="p-3 text-right font-semibold text-gray-900">€ {{ number_format($line->quantity * $line->unit_price, 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="flex justify-end mb-8">
            <div class="w-96">
                <div class="border-b border-gray-200 py-3 flex justify-between">
                    <span class="font-semibold text-gray-700">Subtotaal (excl. BTW)</span>
                    <span class="font-bold text-gray-900">€ {{ number_format($quote->subtotal, 2, ',', '.') }}</span>
                </div>
                <div class="border-b border-gray-200 py-3 flex justify-between">
                    <span class="font-semibold text-gray-700">BTW</span>
                    <span class="font-bold text-gray-900">€ {{ number_format($quote->vat_amount, 2, ',', '.') }}</span>
                </div>
                <div class="bg-blue-900 text-white py-4 px-4 flex justify-between text-lg">
                    <span class="font-bold">TOTAAL (incl. BTW)</span>
                    <span class="font-bold">€ {{ number_format($quote->total, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-blue-900 font-bold text-lg mb-3">💳 Betalingsinformatie</h3>
            <div class="space-y-2 text-sm">
                <div class="flex">
                    <span class="font-semibold text-gray-700 w-40">Rekeningnummer:</span>
                    <span class="text-gray-900">NL12 INGB 0001 2345 67</span>
                </div>
                <div class="flex">
                    <span class="font-semibold text-gray-700 w-40">Ten name van:</span>
                    <span class="text-gray-900">Hallo ICT B.V.</span>
                </div>
                <div class="flex">
                    <span class="font-semibold text-gray-700 w-40">Onder vermelding van:</span>
                    <span class="text-gray-900">{{ $quote->quote_number }}</span>
                </div>
                <div class="flex">
                    <span class="font-semibold text-gray-700 w-40">Geldig tot:</span>
                    <span class="text-gray-900">{{ $quote->valid_until->format('d-m-Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-sm text-gray-500 pt-6 border-t-2 border-gray-200">
            <p class="mb-1">Bedankt voor uw vertrouwen in Hallo ICT!</p>
            <p>Voor vragen over deze factuur kunt u contact met ons opnemen via info@hallo.nl</p>
        </div>
    </div>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
