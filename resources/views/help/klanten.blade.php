<x-app-layout>
    <div class="space-y-8 max-w-5xl" x-data="{ openModal: null }">
        <!-- Breadcrumb + titel -->
        <div>
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('help.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Help & Instructies</a></li>
                    <li class="flex items-center"><svg class="w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                    <li class="font-medium text-gray-900 dark:text-white">Klanten</li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Klanten</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Beheer je klantenbestand: voeg klanten toe, bewerk gegevens en houd alles up-to-date. Klik op de markeringen voor uitleg.</p>
        </div>

        <!-- ==================== SCREENSHOT 1: Klantenlijst ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Klantenlijst</h2>
            <div class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                <img src="{{ asset('images/help/klanten-index.png') }}" alt="Klantenlijst" class="w-full">

                <!-- Marker 1: Nieuwe Klant knop -->
                <button @click="openModal = 1" class="absolute flex items-center justify-center w-8 h-8 bg-blue-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-blue-600/30 hover:bg-blue-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 8%; left: 90%;">1</button>

                <!-- Marker 2: Tabelkolommen -->
                <button @click="openModal = 2" class="absolute flex items-center justify-center w-8 h-8 bg-green-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-green-600/30 hover:bg-green-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 15%; left: 50%;">2</button>

                <!-- Marker 3: Bewerken en Verwijderen -->
                <button @click="openModal = 3" class="absolute flex items-center justify-center w-8 h-8 bg-purple-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-purple-600/30 hover:bg-purple-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 25%; left: 90%;">3</button>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Klik op een nummer voor uitleg over dat onderdeel</p>
        </div>

        <!-- ==================== SCREENSHOT 2: Klant aanmaken modal ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Nieuwe klant aanmaken</h2>
            <div class="relative rounded-lg overflow-hidden border border-gray-200 dark:border-gray-600">
                <img src="{{ asset('images/help/klant-aanmaken-modal.png') }}" alt="Nieuwe klant aanmaken" class="w-full">

                <!-- Marker 4: Naamveld -->
                <button @click="openModal = 4" class="absolute flex items-center justify-center w-8 h-8 bg-blue-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-blue-600/30 hover:bg-blue-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 26%; left: 65%;">4</button>

                <!-- Marker 5: Bedrijfsgegevens -->
                <button @click="openModal = 5" class="absolute flex items-center justify-center w-8 h-8 bg-green-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-green-600/30 hover:bg-green-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 45%; left: 65%;">5</button>

                <!-- Marker 6: Adresgegevens -->
                <button @click="openModal = 6" class="absolute flex items-center justify-center w-8 h-8 bg-amber-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-amber-600/30 hover:bg-amber-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 62%; left: 65%;">6</button>

                <!-- Marker 7: Aanmaken knop -->
                <button @click="openModal = 7" class="absolute flex items-center justify-center w-8 h-8 bg-red-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-red-600/30 hover:bg-red-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 84%; left: 60%;">7</button>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Klik op een nummer voor uitleg over dat onderdeel</p>
        </div>

        <!-- ==================== MODALS ==================== -->

        <template x-teleport="body">
            <div x-show="openModal !== null"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @keydown.escape.window="openModal = null"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 style="display: none;">

                <!-- Backdrop -->
                <div class="absolute inset-0 bg-gray-900/60" @click="openModal = null"></div>

                <!-- Modal 1: Nieuwe Klant knop -->
                <div x-show="openModal === 1"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-blue-600 text-white text-lg font-bold rounded-full">1</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Nieuwe Klant</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Klik op <strong class="text-gray-900 dark:text-white">+ Nieuwe Klant</strong> om een pop-up te openen waarmee je snel een nieuwe klant toevoegt. Je kunt ook klanten aanmaken vanuit het factuur- of offerteformulier.
                        </p>
                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-400">1 van 7</span>
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende: Tabelkolommen
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 2: Tabelkolommen -->
                <div x-show="openModal === 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-green-600 text-white text-lg font-bold rounded-full">2</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Tabelkolommen</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">De klantenlijst toont de volgende gegevens per klant:</p>

                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white w-1/3">Naam</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Contactpersoon van de klant</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Bedrijf</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Bedrijfsnaam (indien ingevuld)</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Email</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">E-mailadres voor correspondentie</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Telefoon</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Telefoonnummer</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Plaats</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Vestigingsplaats van de klant</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-200">
                                <strong>Tip:</strong> Klik op een kolomtitel om de lijst te sorteren op die kolom.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 1" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">2 van 7</span>
                            <button @click="openModal = 3" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 3: Bewerken en Verwijderen -->
                <div x-show="openModal === 3"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-purple-600 text-white text-lg font-bold rounded-full">3</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Bewerken & Verwijderen</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Rechts van elke klant staan twee acties:</p>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-blue-600 dark:text-blue-400 font-medium text-sm">Bewerken</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Open het bewerkformulier om klantgegevens aan te passen.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-red-600 dark:text-red-400 font-medium text-sm">Verwijderen</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Verwijder de klant. Er wordt een bevestiging gevraagd.</p>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
                            <p class="text-xs text-amber-800 dark:text-amber-200">
                                <strong>Let op:</strong> Je kunt een klant niet verwijderen als er nog facturen of offertes aan gekoppeld zijn.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">3 van 7</span>
                            <button @click="openModal = 4" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende: Klant aanmaken
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 4: Naamveld -->
                <div x-show="openModal === 4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-blue-600 text-white text-lg font-bold rounded-full">4</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Naam & Contactgegevens</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">De bovenste velden van het formulier:</p>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                                <div>
                                    <span class="font-semibold text-sm text-blue-900 dark:text-blue-100">Naam *</span>
                                    <p class="text-xs text-blue-800 dark:text-blue-200">Het enige verplichte veld. Vul hier de naam van de contactpersoon of het bedrijf in.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Email</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Nodig voor het versturen van facturen en offertes per e-mail.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Telefoon</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Optioneel telefoonnummer voor je eigen referentie.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 3" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">4 van 7</span>
                            <button @click="openModal = 5" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 5: Bedrijfsgegevens -->
                <div x-show="openModal === 5"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-green-600 text-white text-lg font-bold rounded-full">5</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Bedrijfsgegevens</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Bedrijfsnaam</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">De officiële bedrijfsnaam. Wordt weergegeven op facturen en offertes.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">BTW Nummer</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Het BTW-identificatienummer van de klant, bijv. <code class="text-xs bg-gray-100 dark:bg-gray-600 px-1 py-0.5 rounded">NL123456789B01</code>. Wordt op de factuur vermeld.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 4" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">5 van 7</span>
                            <button @click="openModal = 6" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 6: Adresgegevens -->
                <div x-show="openModal === 6"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-amber-600 text-white text-lg font-bold rounded-full">6</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Adresgegevens</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Het adres van de klant wordt op facturen en offertes weergegeven:</p>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Adres</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Straatnaam en huisnummer.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Postcode & Plaats</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">De postcode en vestigingsplaats.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Land</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Standaard ingesteld op Nederland.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 5" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">6 van 7</span>
                            <button @click="openModal = 7" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 7: Aanmaken knop -->
                <div x-show="openModal === 7"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-red-600 text-white text-lg font-bold rounded-full">7</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Opslaan</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded">Annuleren</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Sluit de pop-up zonder op te slaan.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded">Aanmaken</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Slaat de klant op en voegt deze toe aan je klantenlijst.</p>
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                            Na het aanmaken kun je de klant direct selecteren bij het aanmaken van facturen en offertes.
                        </p>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 6" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">7 van 7</span>
                            <button @click="openModal = null" class="inline-flex items-center gap-1 text-sm font-medium text-green-600 hover:text-green-700 dark:text-green-400">
                                Sluiten
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Veelgestelde vragen -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Veelgestelde vragen</h2>

            <div class="space-y-4">
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Kan ik een klant toevoegen vanuit een factuur?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ja, bij het aanmaken van een factuur of offerte kun je in het klantveld een nieuwe klant toevoegen als deze nog niet bestaat.</p>
                </div>
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Worden klantgegevens automatisch op facturen gezet?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ja, als je een klant selecteert bij het aanmaken van een factuur of offerte worden de naam, het adres, het BTW-nummer en alle andere gegevens automatisch overgenomen.</p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Wat als ik een klant per ongeluk heb verwijderd?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Een verwijderde klant kan niet worden teruggehaald. Facturen en offertes die al voor deze klant zijn aangemaakt blijven bewaard, maar je moet de klant opnieuw aanmaken voor nieuwe documenten.</p>
                </div>
            </div>
        </div>

        <!-- Navigatie onderaan -->
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('help.show', 'offertes') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Offertes
            </a>
            <a href="{{ route('help.show', 'btw-tarieven') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                BTW Tarieven
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</x-app-layout>
