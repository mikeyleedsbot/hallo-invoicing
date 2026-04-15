<x-app-layout>
    <div class="space-y-6" x-data="{
        showEmailModal: false,
        pdfUrl: '',
        mailtoHref: '',
        customerEmail: '',
        startEmailFlow(pdf, href, email) {
            this.pdfUrl = pdf;
            this.mailtoHref = href;
            this.customerEmail = email;
            this.showEmailModal = true;
        },
        executeMailtoFlow() {
            window.open(this.pdfUrl, '_blank');
            setTimeout(() => { window.location.href = this.mailtoHref; }, 400);
            this.showEmailModal = false;
        }
    }">
        
            {{-- Header --}}
            <div class="mb-6 flex justify-between items-start">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Offertes
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Beheer al je offertes
                    </p>
                </div>
                <a href="{{ route('quotes.create') }}"
                    class="text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center gap-2 shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nieuwe Offerte
                </a>
            </div>

            {{-- Success Message --}}
            @if(session('success'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
                {{ session('success') }}
            </div>
            @endif

            {{-- Filters --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <input type="text" placeholder="Zoek offerte..."
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                    </div>
                    <div>
                        <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <option selected>Alle statussen</option>
                            <option>Concept</option>
                            <option>Verzonden</option>
                            <option>Betaald</option>
                            <option>Verlopen</option>
                        </select>
                    </div>
                    <div>
                        <input type="date" placeholder="Van datum"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <input type="date" placeholder="Tot datum"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </form>
            </div>

            {{-- Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                @if($quotes->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Offertenummer</th>
                                    <th scope="col" class="px-6 py-3">Klant</th>
                                    <th scope="col" class="px-6 py-3">Datum</th>
                                    <th scope="col" class="px-6 py-3">Geldig tot</th>
                                    <th scope="col" class="px-6 py-3">Bedrag</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quotes as $quote)
                                @php
                                    $_cust        = $quote->customer;
                                    $_sender      = auth()->user()->company_name ?: auth()->user()->name;
                                    $_custSal     = $_cust->contact_person ?: $_cust->name;
                                    $_amountFmt   = number_format($quote->total_including_vat ?? $quote->total ?? 0, 2, ',', '.');
                                    $_validFmt    = $quote->valid_until ? \Carbon\Carbon::parse($quote->valid_until)->format('d-m-Y') : '';
                                    $_mailSubject = 'Offerte ' . $quote->quote_number . ' van ' . $_sender;
                                    $_mailBodyLines = [
                                        'Beste ' . $_custSal . ',',
                                        '',
                                        'Bijgaand de offerte ' . $quote->quote_number . ' voor een bedrag van EUR ' . $_amountFmt . '.',
                                    ];
                                    if ($_validFmt) {
                                        $_mailBodyLines[] = 'Deze offerte is geldig tot en met ' . $_validFmt . '.';
                                    }
                                    $_mailBodyLines = array_merge($_mailBodyLines, [
                                        '',
                                        'Met vriendelijke groet,',
                                        $_sender,
                                    ]);
                                    $_mailBody   = implode("\n", $_mailBodyLines);
                                    $_mailtoHref = 'mailto:' . rawurlencode($_cust->email ?? '')
                                        . '?subject=' . rawurlencode($_mailSubject)
                                        . '&body=' . rawurlencode($_mailBody);
                                    $_pdfUrl   = route('quotes.pdf', $quote);
                                    $_hasEmail = !empty($_cust->email);
                                @endphp
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                        {{ $quote->quote_number }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $quote->customer->name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ \Carbon\Carbon::parse($quote->quote_date)->format('d-m-Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ \Carbon\Carbon::parse($quote->valid_until)->format('d-m-Y') }}
                                    </td>
                                    <td class="px-6 py-4 font-medium">
                                        € {{ number_format($quote->total, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4">
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
                                        <span class="text-xs font-medium px-2.5 py-0.5 rounded {{ $statusColors[$quote->status] ?? '' }}">
                                            {{ $statusLabels[$quote->status] ?? ucfirst($quote->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            {{-- PDF Download --}}
                                            <a href="{{ route('quotes.pdf', $quote) }}"
                                                class="text-purple-600 hover:text-purple-800 dark:text-purple-500 dark:hover:text-purple-400"
                                                title="Download PDF">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                                                </svg>
                                            </a>
                                            
                                            {{-- Print --}}
                                            <a href="{{ route('quotes.print', $quote) }}" target="_blank"
                                                class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                                title="Afdrukken">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                                </svg>
                                            </a>
                                            
                                            {{-- Email versturen --}}
                                            @if($_hasEmail)
                                            <button type="button"
                                                @click="startEmailFlow(@js($_pdfUrl), @js($_mailtoHref), @js($_cust->email))"
                                                class="text-green-600 hover:text-green-800 dark:text-green-500 dark:hover:text-green-400"
                                                title="E-mail versturen naar {{ $_cust->email }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                            @else
                                            <span class="text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                                title="Klant heeft geen e-mailadres">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </span>
                                            @endif

                                            {{-- View --}}
                                            <a href="{{ route('quotes.show', $quote) }}"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-400"
                                                title="Bekijken">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            
                                            {{-- Edit --}}
                                            <a href="{{ route('quotes.edit', $quote) }}"
                                                class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
                                                title="Bewerken">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            
                                            {{-- Delete --}}
                                            <form action="{{ route('quotes.destroy', $quote) }}" method="POST" class="inline"
                                                onsubmit="return confirm('Weet je zeker dat je deze offerte wilt verwijderen?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-500 dark:hover:text-red-400"
                                                    title="Verwijderen">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $quotes->links() }}
                    </div>
                @else
                    {{-- Empty State --}}
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Geen facturen</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kom op gang door je eerste offerte aan te maken.</p>
                        <div class="mt-6">
                            <a href="{{ route('quotes.create') }}"
                                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Nieuwe Offerte
                            </a>
                        </div>
                    </div>
                @endif
            </div>

        {{-- Email Modal --}}
        <div x-show="showEmailModal" x-cloak
             class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showEmailModal = false"></div>
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.stop class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg">
                    <div class="flex items-start justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Offerte per e-mail versturen</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="'Naar: ' + customerEmail"></p>
                        </div>
                        <button type="button" @click="showEmailModal = false"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm text-blue-900 dark:text-blue-200 font-medium mb-2">Hoe werkt dit?</p>
                            <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-decimal list-inside">
                                <li>We downloaden de offerte als PDF naar je computer.</li>
                                <li>We openen je standaard e-mailprogramma met een vooringevulde mail naar je klant.</li>
                                <li>Voeg de zojuist gedownloade PDF toe als bijlage en klik op verzenden.</li>
                            </ol>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <p class="text-xs text-amber-800 dark:text-amber-300">
                                💡 <strong>Tip:</strong> Stel eenmalig een Google Workspace of Microsoft 365 account in bij je e-mailverbindingen om offertes voortaan met één klik vanuit Hallo Invoicing te versturen — zonder bijlage toe te voegen.
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" @click="showEmailModal = false"
                                class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            Annuleren
                        </button>
                        <button type="button" @click="executeMailtoFlow()"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            PDF downloaden + E-mail openen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
