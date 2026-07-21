<x-app-layout>
    <div class="space-y-6">
        
            {{-- Header --}}
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Instellingen
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Algemene applicatie instellingen
                </p>
            </div>

            {{-- Success Message --}}
            @if(session('success'))
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">
                {{ session('success') }}
            </div>
            @endif

            {{-- Validatiefouten --}}
            @if($errors->any())
            <div class="p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
                <p class="font-medium mb-1">Opslaan is niet gelukt. Controleer de volgende velden:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            {{-- Form --}}
            <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Standaard Waarden --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Standaard Waarden</h2>
                    <div class="grid gap-4 grid-cols-1 md:grid-cols-3">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Standaard BTW Tarief (%)
                            </label>
                            <input type="number" name="default_vat_rate" value="{{ old('default_vat_rate', $settings->default_vat_rate) }}" step="0.01" min="0" max="100" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Betalingstermijn (dagen)
                            </label>
                            <input type="number" name="default_payment_terms" value="{{ old('default_payment_terms', $settings->default_payment_terms) }}" min="1" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Offerte Geldigheid (dagen)
                            </label>
                            <input type="number" name="quote_valid_days" value="{{ old('quote_valid_days', $settings->quote_valid_days) }}" min="1" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>
                    </div>
                </div>

                {{-- Valuta & Opmaak --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Valuta & Opmaak</h2>
                    <div class="grid gap-4 grid-cols-1 md:grid-cols-3">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Valuta
                            </label>
                            <select name="currency" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="EUR" {{ $settings->currency === 'EUR' ? 'selected' : '' }}>EUR (Euro)</option>
                                <option value="USD" {{ $settings->currency === 'USD' ? 'selected' : '' }}>USD (Dollar)</option>
                                <option value="GBP" {{ $settings->currency === 'GBP' ? 'selected' : '' }}>GBP (Pond)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Valuta Symbool
                            </label>
                            <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings->currency_symbol) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Datum Formaat
                            </label>
                            <select name="date_format" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="d-m-Y" {{ $settings->date_format === 'd-m-Y' ? 'selected' : '' }}>31-12-2026</option>
                                <option value="Y-m-d" {{ $settings->date_format === 'Y-m-d' ? 'selected' : '' }}>2026-12-31</option>
                                <option value="m/d/Y" {{ $settings->date_format === 'm/d/Y' ? 'selected' : '' }}>12/31/2026</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Nummering --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nummering</h2>
                    <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Factuur Prefix
                            </label>
                            <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $settings->invoice_prefix) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="INV">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bijvoorbeeld: {{ $settings->invoice_prefix ?? 'INV' }}00001</p>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Offerte Prefix
                            </label>
                            <input type="text" name="quote_prefix" value="{{ old('quote_prefix', $settings->quote_prefix) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="OFF">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Bijvoorbeeld: {{ $settings->quote_prefix ?? 'OFF' }}00001</p>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Factuurteller
                            </label>
                            <input type="number" name="invoice_number_start" min="1" max="99999"
                                value="{{ old('invoice_number_start', $settings->invoice_number_start ?? 1) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Volgende factuur wordt <strong>{{ $settings->nextInvoiceNumber() }}</strong>.
                                De teller schuift na elke factuur automatisch door; al bestaande nummers worden overgeslagen.
                            </p>
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                                Offerteteller
                            </label>
                            <input type="number" name="quote_number_start" min="1" max="99999"
                                value="{{ old('quote_number_start', $settings->quote_number_start ?? 1) }}" required
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Volgende offerte wordt <strong>{{ $settings->nextQuoteNumber() }}</strong>.
                                De teller schuift na elke offerte automatisch door; al bestaande nummers worden overgeslagen.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Kredietbeperking --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
                     x-data="{ surchargeEnabled: {{ old('credit_surcharge_enabled', $settings->credit_surcharge_enabled) ? 'true' : 'false' }} }">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Kredietbeperking</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Toeslag die vervalt bij tijdige betaling. Wordt berekend over het factuurtotaal
                        <strong>inclusief btw</strong> en zonder btw over de toeslag zelf, conform de richtlijn
                        van de Belastingdienst.
                    </p>

                    <div class="flex items-center gap-3 mb-4">
                        <input type="checkbox" id="credit_surcharge_enabled" name="credit_surcharge_enabled" value="1"
                            x-model="surchargeEnabled"
                            class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                        <label for="credit_surcharge_enabled" class="text-sm text-gray-700 dark:text-gray-300">
                            Kredietbeperking toepassen op facturen
                        </label>
                    </div>

                    <div x-show="surchargeEnabled" x-transition>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                            Percentage
                        </label>
                        <select name="credit_surcharge_percent"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full md:w-48 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            @foreach([1, 2, 3, 4, 5] as $pct)
                                <option value="{{ $pct }}" {{ (int) old('credit_surcharge_percent', $settings->credit_surcharge_percent ?? 2) === $pct ? 'selected' : '' }}>
                                    {{ $pct }}%
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Zet daarna in de template-editor de velden <strong>Kredietbeperking</strong> en
                            <strong>Totaal incl. kredietbeperking</strong> op je factuurtemplate (onder "Factuur Info").
                        </p>
                    </div>
                </div>

                {{-- E-mailteksten --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">E-mailteksten</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Deze tekst wordt gebruikt bij het e-mailen van facturen en offertes &mdash; zowel bij direct versturen via je mailverbinding als bij de vooringevulde mail in je eigen mailprogramma. De onderstaande velden zijn alvast gevuld met een standaardtekst; pas ze gerust aan.
                    </p>

                    @php
                        $emailEditors = [
                            [
                                'label'        => 'Factuur-e-mail',
                                'subjectField' => 'invoice_email_subject',
                                'bodyField'    => 'invoice_email_body',
                                'subject'      => old('invoice_email_subject', $settings->invoiceEmailSubject()),
                                'body'         => old('invoice_email_body', $settings->invoiceEmailBody()),
                                'placeholders' => ['{contactpersoon}', '{klantnaam}', '{nummer}', '{bedrag}', '{vervaldatum}', '{bedrijfsnaam}'],
                            ],
                            [
                                'label'        => 'Offerte-e-mail',
                                'subjectField' => 'quote_email_subject',
                                'bodyField'    => 'quote_email_body',
                                'subject'      => old('quote_email_subject', $settings->quoteEmailSubject()),
                                'body'         => old('quote_email_body', $settings->quoteEmailBody()),
                                'placeholders' => ['{contactpersoon}', '{klantnaam}', '{nummer}', '{bedrag}', '{geldig_tot}', '{bedrijfsnaam}'],
                            ],
                        ];
                    @endphp

                    <div class="space-y-8">
                        @foreach($emailEditors as $editor)
                        <div x-data="{
                                value: @js($editor['body']),
                                sync() { this.value = this.$refs.area.innerHTML; },
                                exec(cmd) { this.$refs.area.focus(); document.execCommand(cmd, false, null); this.sync(); },
                                insert(ph) { this.$refs.area.focus(); document.execCommand('insertText', false, ph); this.sync(); }
                             }"
                             x-init="$refs.area.innerHTML = value">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">{{ $editor['label'] }}</h3>

                            <div class="mb-3">
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Onderwerp</label>
                                <input type="text" name="{{ $editor['subjectField'] }}" value="{{ $editor['subject'] }}"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>

                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Berichttekst</label>
                            <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden">
                                {{-- Toolbar --}}
                                <div class="flex items-center gap-1 px-2 py-1.5 bg-gray-50 dark:bg-gray-700 border-b border-gray-300 dark:border-gray-600">
                                    <button type="button" @click="exec('bold')" title="Vetgedrukt"
                                            class="w-8 h-8 rounded font-bold text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">B</button>
                                    <button type="button" @click="exec('italic')" title="Cursief"
                                            class="w-8 h-8 rounded italic text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">I</button>
                                    <button type="button" @click="exec('underline')" title="Onderstreept"
                                            class="w-8 h-8 rounded underline text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">U</button>
                                    <span class="w-px h-5 bg-gray-300 dark:bg-gray-600 mx-1"></span>
                                    <button type="button" @click="exec('insertUnorderedList')" title="Opsomming"
                                            class="w-8 h-8 rounded text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 inline-flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h.01M4 12h.01M4 18h.01M8 6h12M8 12h12M8 18h12"/></svg>
                                    </button>
                                    <button type="button" @click="exec('removeFormat')" title="Opmaak wissen"
                                            class="w-8 h-8 rounded text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 inline-flex items-center justify-center">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                {{-- Editor --}}
                                <div x-ref="area" contenteditable="true" @input="sync()" @blur="sync()"
                                     class="bg-white dark:bg-gray-800 p-4 text-sm text-gray-900 dark:text-white focus:outline-none"
                                     style="min-height: 10rem;"></div>
                            </div>
                            <textarea name="{{ $editor['bodyField'] }}" x-model="value" class="hidden"></textarea>

                            {{-- Placeholders --}}
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="text-xs text-gray-500 dark:text-gray-400 mr-1">Klik om in te voegen:</span>
                                @foreach($editor['placeholders'] as $ph)
                                <button type="button" @click="insert('{{ $ph }}')"
                                        class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-xs font-mono text-gray-700 dark:text-gray-200 hover:bg-blue-50 hover:border-blue-300 dark:hover:bg-gray-600">{{ $ph }}</button>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Info Box --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="text-sm text-blue-800 dark:text-blue-300">
                            <p class="font-semibold mb-1">Let op</p>
                            <p>Deze instellingen worden gebruikt als standaardwaarden bij het aanmaken van nieuwe facturen en offertes. Je kunt deze waarden per document nog aanpassen.</p>
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="flex justify-end">
                    <button type="submit"
                        class="text-white bg-gradient-to-r from-blue-500 to-blue-700 hover:from-blue-600 hover:to-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-8 py-3">
                        Opslaan
                    </button>
                </div>
            </form>

            @if(false) {{-- placeholder, verwijderd --}}
            <div>

                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Gebruikersbeheer
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Beheer accounts die toegang hebben tot de invoicing tool</p>
                    </div>
                    <button type="button" @click="showCreate = !showCreate"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nieuwe gebruiker
                    </button>
                </div>

                {{-- Aanmaakformulier --}}
                <div x-show="showCreate" x-transition class="mb-6 p-5 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Nieuwe gebruiker aanmaken</h3>
                    <form method="POST" action="{{ route('settings.users.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Naam</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mailadres</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wachtwoord</label>
                            <input type="password" name="password" required
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Wachtwoord bevestigen</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="md:col-span-2 flex items-center gap-3">
                            <input type="checkbox" name="is_admin" id="new_is_admin" value="1"
                                   class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                            <label for="new_is_admin" class="text-sm text-gray-700 dark:text-gray-300">Hallo Admin (toegang tot gebruikersbeheer)</label>
                        </div>
                        <div class="md:col-span-2 flex gap-3">
                            <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">Aanmaken</button>
                            <button type="button" @click="showCreate = false" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">Annuleren</button>
                        </div>
                    </form>
                </div>

                {{-- Gebruikerslijst --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th class="px-5 py-3">Naam</th>
                                <th class="px-5 py-3">E-mail</th>
                                <th class="px-5 py-3">Rol</th>
                                <th class="px-5 py-3">MFA</th>
                                <th class="px-5 py-3 text-right">Acties</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($users as $u)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors" x-data="{ editing: false }">
                                <td class="px-5 py-3">
                                    <div x-show="!editing" class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-xs font-bold text-gray-600 dark:text-gray-300 flex-shrink-0">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $u->name }}</span>
                                        @if($u->id === Auth::id())<span class="text-xs text-gray-400 dark:text-gray-500">(jij)</span>@endif
                                    </div>
                                    <div x-show="editing">
                                        <input type="text" form="edit-user-{{ $u->id }}" name="name" value="{{ $u->name }}" required
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-sm">
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div x-show="!editing" class="text-gray-600 dark:text-gray-300">{{ $u->email }}</div>
                                    <div x-show="editing">
                                        <input type="email" form="edit-user-{{ $u->id }}" name="email" value="{{ $u->email }}" required
                                               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-2 py-1 text-sm">
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div x-show="!editing">
                                        @if($u->is_admin)
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Admin</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Gebruiker</span>
                                        @endif
                                    </div>
                                    @if($u->id !== Auth::id())
                                    <div x-show="editing" class="flex items-center gap-2">
                                        <input type="checkbox" form="edit-user-{{ $u->id }}" name="is_admin" value="1" {{ $u->is_admin ? 'checked' : '' }}
                                               class="w-4 h-4 text-blue-600 rounded border-gray-300">
                                        <label class="text-xs text-gray-600 dark:text-gray-400">Admin</label>
                                    </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    @if($u->mfa_enabled)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Actief
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Niet ingesteld</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right">
                                    {{-- Hidden edit form --}}
                                    <form id="edit-user-{{ $u->id }}" method="POST" action="{{ route('settings.users.update', $u) }}">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="password" value="">
                                        <input type="hidden" name="password_confirmation" value="">
                                    </form>

                                    <div x-show="!editing" class="flex items-center justify-end gap-2">
                                        <button type="button" @click="editing = true"
                                                class="px-3 py-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                            Bewerken
                                        </button>
                                        @if($u->mfa_enabled)
                                        <form method="POST" action="{{ route('settings.users.reset-mfa', $u) }}" class="inline">
                                            @csrf
                                            <button type="submit" onclick="return confirm('MFA resetten voor {{ $u->name }}?')"
                                                    class="px-3 py-1.5 text-xs font-medium text-amber-600 border border-amber-200 rounded-lg hover:bg-amber-50 transition-colors">
                                                MFA resetten
                                            </button>
                                        </form>
                                        @endif
                                        @if($u->id !== Auth::id())
                                        <form method="POST" action="{{ route('settings.users.destroy', $u) }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Gebruiker {{ $u->name }} verwijderen?')"
                                                    class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                                Verwijderen
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                    <div x-show="editing" class="flex items-center justify-end gap-2">
                                        <button type="submit" form="edit-user-{{ $u->id }}"
                                                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                            Opslaan
                                        </button>
                                        <button type="button" @click="editing = false"
                                                class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                            Annuleren
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
            @endif

        </div>
    </div>
</x-app-layout>
