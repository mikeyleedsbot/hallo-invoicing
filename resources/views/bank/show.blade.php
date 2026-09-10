<x-app-layout>
@section('title', 'Transacties matchen')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <a href="{{ route('bank.index') }}" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-500">&larr; Terug naar bankafschriften</a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-1">Transacties matchen</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $session->original_filename }} &middot; {{ $session->formatLabel() }}
                    @if($session->period_from) &middot; {{ $session->period_from->format('d-m-Y') }} t/m {{ $session->period_to?->format('d-m-Y') }}@endif
                </p>
            </div>

            @if($session->isOpen())
            <form action="{{ route('bank.complete', $session) }}" method="POST"
                  onsubmit="return confirm('Matching afronden? Alle transacties die nergens aan gekoppeld zijn worden verwijderd.')">
                @csrf
                <button type="submit" class="px-5 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg">
                    Matching afronden
                </button>
            </form>
            @endif
        </div>

        @if(session('success'))
            <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Al gekoppeld. Standaard dicht: bij een groot afschrift zijn dit er
             al snel honderden en dan is de rest van de pagina niet te vinden. --}}
        <div x-data="{ open: false, alles: false }" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <button type="button" @click="open = ! open" class="flex items-center gap-2 text-left">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" :class="open ? 'rotate-90' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Gekoppeld ({{ $matched->count() }})
                    </h2>
                </button>

                @if($matched->isNotEmpty())
                <button type="button" x-show="open" x-cloak @click="alles = ! alles"
                        class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">
                    <span x-show="! alles">Alle betaalgegevens tonen</span>
                    <span x-show="alles" x-cloak>Alle betaalgegevens verbergen</span>
                </button>
                @endif
            </div>

            @if($matched->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">Nog niets gekoppeld.</p>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2" x-show="! open">
                    Klap uit om te zien wat er gekoppeld is en om een koppeling na te kijken.
                </p>
                <div x-show="open" x-cloak class="space-y-2 mt-4">
                    @foreach($matched as $payment)
                    @php $t = $payment->transaction; @endphp
                    <div x-data="{ detail: false }" x-init="$watch('alles', waarde => detail = waarde)"
                         class="p-3 rounded-lg border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <div class="text-sm text-gray-900 dark:text-gray-100 min-w-0">
                                <span class="font-medium">€ {{ number_format($payment->amount, 2, ',', '.') }}</span>
                                naar factuur
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-blue-600 hover:underline dark:text-blue-400">{{ $payment->invoice?->invoice_number }}</a>
                                @if($payment->matched_by === 'auto')
                                    <span class="ms-1 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">automatisch</span>
                                @else
                                    <span class="ms-1 px-2 py-0.5 rounded-full text-xs bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200">handmatig</span>
                                @endif
                                {{-- Zonder uitklappen al genoeg om de koppeling te herkennen --}}
                                <span class="text-gray-500 dark:text-gray-400">
                                    &middot; {{ $t?->booking_date?->format('d-m-Y') }}
                                    &middot; {{ $t?->counterparty_name ?: 'onbekend' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="detail = ! detail"
                                        class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-100 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg">
                                    <span x-show="! detail">Betaalgegevens</span>
                                    <span x-show="detail" x-cloak>Verbergen</span>
                                </button>
                                <form action="{{ route('bank.unlink', $payment) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-100 dark:text-red-400 dark:hover:bg-red-900/30 rounded-lg">
                                        Koppeling ongedaan maken
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div x-show="detail" x-cloak class="grid gap-3 sm:grid-cols-2 mt-2">
                            {{-- Wat er van de bank binnenkwam --}}
                            <div class="p-2 rounded bg-white/70 dark:bg-gray-800/60 border border-green-200 dark:border-green-800">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Betaling</p>
                                <dl class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5">
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Datum</dt>
                                        <dd>{{ $t?->booking_date?->format('d-m-Y') ?: 'onbekend' }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Bedrag</dt>
                                        <dd>€ {{ number_format($t?->amount ?? $payment->amount, 2, ',', '.') }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Van</dt>
                                        <dd class="break-words">{{ $t?->counterparty_name ?: 'onbekend' }}</dd>
                                    </div>
                                    @if($t?->counterparty_iban)
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">IBAN</dt>
                                        <dd class="break-all font-mono">{{ $t->counterparty_iban }}</dd>
                                    </div>
                                    @endif
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Omschrijving</dt>
                                        <dd class="break-words">{{ $t?->description ?: '—' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- Waar het aan gekoppeld is --}}
                            <div class="p-2 rounded bg-white/70 dark:bg-gray-800/60 border border-green-200 dark:border-green-800">
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Factuur</p>
                                <dl class="text-xs text-gray-700 dark:text-gray-300 space-y-0.5">
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Nummer</dt>
                                        <dd>{{ $payment->invoice?->invoice_number }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Klant</dt>
                                        <dd class="break-words">{{ $payment->invoice?->customer?->company_name ?: $payment->invoice?->customer?->name }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Datum</dt>
                                        <dd>{{ $payment->invoice?->invoice_date?->format('d-m-Y') }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Totaal</dt>
                                        <dd>€ {{ number_format($payment->invoice?->total ?? 0, 2, ',', '.') }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="w-24 shrink-0 text-gray-500 dark:text-gray-400">Nog open</dt>
                                        <dd>€ {{ number_format($payment->invoice?->outstandingAmount() ?? 0, 2, ',', '.') }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Al eerder geïmporteerd --}}
        @if($recognised->isNotEmpty())
        <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <button type="button" @click="open = ! open" class="flex items-center gap-2 text-left">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" :class="open ? 'rotate-90' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Al eerder geïmporteerd ({{ $recognised->count() }})
                </h2>
            </button>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 ms-6">
                Deze regels stonden er al van een vorige import en zijn niet opnieuw toegevoegd.
            </p>

            <div x-show="open" x-cloak class="space-y-2 mt-4">
                @foreach($recognised as $t)
                <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-lg border border-blue-200 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800">
                    <div class="text-sm text-gray-900 dark:text-gray-100 min-w-0">
                        <span class="font-medium">€ {{ number_format($t->amount, 2, ',', '.') }}</span>
                        <span class="text-gray-600 dark:text-gray-400">
                            &middot; {{ $t->booking_date?->format('d-m-Y') }}
                            &middot; {{ $t->counterparty_name ?: 'onbekend' }}
                        </span>
                        @if($t->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 break-words">
                                <span class="text-gray-400 dark:text-gray-500">Omschrijving:</span> {{ $t->description }}
                            </div>
                        @endif
                    </div>
                    <div class="text-sm">
                        @forelse($t->payments as $payment)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                gekoppeld aan
                                <a href="{{ route('invoices.show', $payment->invoice) }}" class="underline">{{ $payment->invoice?->invoice_number }}</a>
                            </span>
                        @empty
                            <a href="{{ route('bank.show', $t->bank_import_session_id) }}"
                               class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                staat nog open in een andere import
                            </a>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Nog te matchen --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                Nog te matchen ({{ count($suggestions) }})
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Alleen bijschrijvingen kunnen een verkoopfactuur betalen; afschrijvingen staan hier niet tussen.
                Eén betaling mag over meerdere facturen verdeeld worden: koppel een deel, dan blijft het restant hier staan.
            </p>
            <div class="flex flex-wrap items-center gap-4 mb-4 text-xs text-gray-600 dark:text-gray-400">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-green-600"></span>
                    Vanaf {{ \App\Services\BankImport\InvoiceMatcher::AUTO_THRESHOLD }}%: sterk genoeg om automatisch te koppelen
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                    Redelijk vermoeden, even nakijken
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    Zwak signaal
                </span>
            </div>

            @if($suggestions === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">Alle bijschrijvingen zijn gekoppeld.</p>
            @else
                <div class="space-y-4">
                    @foreach($suggestions as $row)
                        @php $t = $row['transaction']; @endphp
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-3">
                                <div>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">€ {{ number_format($t->unallocatedAmount(), 2, ',', '.') }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400 ms-2">{{ $t->booking_date?->format('d-m-Y') }}</span>
                                    @if($t->allocatedAmount() > 0)
                                        <span class="ms-2 text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                            nog te verdelen van € {{ number_format($t->amount, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-700 dark:text-gray-300 text-right">
                                    {{ $t->counterparty_name ?: 'Onbekende tegenpartij' }}
                                    @if($t->counterparty_iban)
                                        <div class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $t->counterparty_iban }}</div>
                                    @endif
                                </div>
                            </div>

                            <p class="text-xs text-gray-600 dark:text-gray-400 mb-3 break-words">
                                <span class="text-gray-400 dark:text-gray-500">Omschrijving:</span> {{ $t->description ?: '—' }}
                            </p>

                            {{-- Al verdeelde delen van deze betaling. Eén bijschrijving mag over
                                 meerdere facturen verdeeld worden; wat overblijft koppel je hieronder. --}}
                            @if($t->payments->isNotEmpty())
                                <div class="mb-3 p-2 rounded border border-green-200 bg-green-50 dark:bg-green-900/20 dark:border-green-800">
                                    <p class="text-xs font-medium text-green-900 dark:text-green-100 mb-1">
                                        Al verdeeld over {{ $t->payments->count() }}{{ $t->payments->count() === 1 ? ' factuur' : ' facturen' }}
                                    </p>
                                    @foreach($t->payments as $deel)
                                    <div class="flex items-center justify-between gap-2 text-xs text-gray-800 dark:text-gray-200 py-0.5">
                                        <span>
                                            € {{ number_format($deel->amount, 2, ',', '.') }} naar
                                            <a href="{{ route('invoices.show', $deel->invoice) }}" class="underline">{{ $deel->invoice?->invoice_number }}</a>
                                        </span>
                                        <form action="{{ route('bank.unlink', $deel) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">ongedaan maken</button>
                                        </form>
                                    </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($row['candidates'] !== [])
                                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">Waarschijnlijke facturen</p>
                                <div class="space-y-2 mb-3">
                                    @foreach(array_slice($row['candidates'], 0, 4) as $c)
                                    @php
                                        // Groen vanaf de drempel waarop ook automatisch gekoppeld zou worden,
                                        // oranje bij een redelijk vermoeden, grijs bij een zwak signaal
                                        $sterk = $c['score'] >= \App\Services\BankImport\InvoiceMatcher::AUTO_THRESHOLD;
                                        $redelijk = ! $sterk && $c['score'] >= 55;
                                        $rij = $sterk
                                            ? 'border-green-300 bg-green-50 dark:bg-green-900/20 dark:border-green-700'
                                            : ($redelijk
                                                ? 'border-orange-300 bg-orange-50 dark:bg-orange-900/20 dark:border-orange-700'
                                                : 'border-gray-200 bg-gray-50 dark:bg-gray-700/40 dark:border-gray-600');
                                        $badge = $sterk
                                            ? 'bg-green-600 text-white'
                                            : ($redelijk ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-200');
                                    @endphp
                                    <form action="{{ route('bank.link', $t) }}" method="POST"
                                          class="flex flex-wrap items-center gap-2 p-2 rounded border {{ $rij }}">
                                        @csrf
                                        <input type="hidden" name="invoice_id" value="{{ $c['invoice']->id }}">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $c['invoice']->invoice_number }}</span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $c['invoice']->customer?->company_name ?: $c['invoice']->customer?->name }}
                                        </span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            openstaand € {{ number_format($c['invoice']->outstandingAmount(), 2, ',', '.') }}
                                        </span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badge }}">{{ $c['score'] }}%</span>
                                        <span class="text-xs text-gray-600 dark:text-gray-400">{{ implode(', ', $c['reasons']) }}</span>
                                        <span class="ms-auto flex items-center gap-2">
                                            <label class="text-xs text-gray-500 dark:text-gray-400">€</label>
                                            <input type="number" name="amount" step="0.01" min="0.01" value="{{ number_format($c['amount'], 2, '.', '') }}"
                                                   class="w-28 px-2 py-1 text-sm border border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                            <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Koppel</button>
                                        </span>
                                    </form>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Geen waarschijnlijke factuur gevonden.</p>
                            @endif

                            {{-- Handmatig een factuur kiezen --}}
                            <form action="{{ route('bank.link', $t) }}" method="POST" class="flex flex-wrap items-center gap-2 pt-3 border-t border-gray-200 dark:border-gray-600">
                                @csrf
                                <label class="text-xs text-gray-500 dark:text-gray-400">Handmatig:</label>
                                <select name="invoice_id" required
                                        x-tom-select="{
                                            options: window.__bankInvoices,
                                            valueField: 'id',
                                            labelField: 'label',
                                            searchField: ['number', 'customer', 'label'],
                                            maxOptions: 50,
                                            placeholder: 'Zoek op factuurnummer of klant…'
                                        }"
                                        class="flex-1 min-w-[14rem]"></select>
                                <label class="text-xs text-gray-500 dark:text-gray-400">€</label>
                                <input type="number" name="amount" step="0.01" min="0.01" value="{{ number_format($t->unallocatedAmount(), 2, '.', '') }}"
                                       class="w-28 px-2 py-1 text-sm border border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <button type="submit" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                                    Koppel
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Facturenlijst één keer meegeven: de zoekdropdowns delen deze data,
         zodat er niet per transactie duizenden <option>-regels in de HTML komen --}}
    <script>
        window.__bankInvoices = @json($invoiceOptions);
    </script>
</x-app-layout>
