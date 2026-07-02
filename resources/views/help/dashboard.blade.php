<x-app-layout>
    <div class="space-y-8" x-data="{ openModal: null }">
        <!-- Breadcrumb + titel -->
        <div>
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('help.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Help & Instructies</a></li>
                    <li class="flex items-center"><svg class="w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                    <li class="font-medium text-gray-900 dark:text-white">Dashboard</li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Het dashboard is je startpagina. Klik op de genummerde markeringen in het voorbeeld voor uitleg over elk onderdeel.</p>
        </div>

        <!-- ==================== VOORBEELD: Dashboard ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="overflow-x-auto pt-4">
                {{-- Nagebouwd voorbeeld: identiek aan het echte dashboard --}}
                <div class="flex gap-4" style="min-width: 860px;">

                    {{-- Sidebar (vereenvoudigd) --}}
                    <div class="relative flex-shrink-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3" style="width: 11rem;">
                        <button @click="openModal = 5" class="absolute flex items-center justify-center w-8 h-8 bg-red-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-red-600/30 hover:bg-red-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; right: -16px; z-index: 10;">5</button>
                        <div class="space-y-1 text-sm">
                            <div class="px-2 py-1.5 rounded bg-blue-600 text-white font-medium">Dashboard</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Facturen</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Offertes</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Klanten</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Producten</div>
                            <div class="pt-2 px-2 text-xs font-semibold text-gray-400 uppercase dark:text-gray-500">Instellingen</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Bedrijfsgegevens</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Templates</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">BTW Tarieven</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Instellingen</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">E-mailverbindingen</div>
                            <div class="px-2 py-1.5 rounded text-gray-700 dark:text-gray-300">Help & Instructies</div>
                        </div>
                    </div>

                    {{-- Hoofdinhoud --}}
                    <div class="flex-1 space-y-4">
                        {{-- Welkom --}}
                        <div class="relative">
                            <button @click="openModal = 1" class="absolute flex items-center justify-center w-8 h-8 bg-blue-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-blue-600/30 hover:bg-blue-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -8px; right: -8px; z-index: 10;">1</button>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">Welkom terug, Piet! 👋</span>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Hier is een overzicht van je facturatie activiteiten.</p>
                        </div>

                        {{-- Statistiekkaarten --}}
                        <div class="relative grid grid-cols-4 gap-3">
                            <button @click="openModal = 2" class="absolute flex items-center justify-center w-8 h-8 bg-green-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-green-600/30 hover:bg-green-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">2</button>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 mb-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Openstaand</p>
                                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">€ 3.850,00</p>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">3 facturen • onbetaald</p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-green-500 to-green-600 mb-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Deze Maand</p>
                                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">€ 5.200,00</p>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">4 facturen • juli</p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">+2 nieuw</span>
                                </div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Klanten</p>
                                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">12</p>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Actieve relaties</p>
                            </div>
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">1 verstuurd</span>
                                </div>
                                <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Offertes</p>
                                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">3</p>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">2 concepten • 1 wacht op goedkeuring</p>
                            </div>
                        </div>

                        {{-- Snelle Acties --}}
                        <div class="relative bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-4">
                            <button @click="openModal = 3" class="absolute flex items-center justify-center w-8 h-8 bg-purple-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-purple-600/30 hover:bg-purple-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">3</button>
                            <span class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                                Snelle Acties
                            </span>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl shadow-sm">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-white/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold block text-sm">Nieuwe Factuur</span>
                                        <span class="text-xs text-blue-100">Maak een factuur aan</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold block text-sm">Nieuwe Offerte</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Stuur een offerte</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-green-600 text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    </div>
                                    <div>
                                        <span class="font-semibold block text-sm">Klanten</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">12 klanten beheren</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Recente Facturen --}}
                        <div class="relative bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                            <button @click="openModal = 4" class="absolute flex items-center justify-center w-8 h-8 bg-amber-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-amber-600/30 hover:bg-amber-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 8px; left: 50%; margin-left: -16px; z-index: 10;">4</button>
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <span class="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Recente Facturen
                                </span>
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400 flex items-center gap-1">Bekijk alle <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></span>
                            </div>
                            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th class="px-4 py-2">Factuurnummer</th>
                                        <th class="px-4 py-2">Klant</th>
                                        <th class="px-4 py-2">Bedrag</th>
                                        <th class="px-4 py-2">Datum</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">2026001</td>
                                        <td class="px-4 py-3">Bakkerij De Gouden Korst</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">€ 1.250,00</td>
                                        <td class="px-4 py-3">01-07-2026</td>
                                        <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Verzonden</span></td>
                                        <td class="px-4 py-3 text-right"><span class="text-blue-600 dark:text-blue-400 font-medium text-xs">Bekijken →</span></td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">2026002</td>
                                        <td class="px-4 py-3">Jansen &amp; Zonen B.V.</td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">€ 3.450,00</td>
                                        <td class="px-4 py-3">25-06-2026</td>
                                        <td class="px-4 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Betaald</span></td>
                                        <td class="px-4 py-3 text-right"><span class="text-blue-600 dark:text-blue-400 font-medium text-xs">Bekijken →</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Nagebouwd voorbeeld van de echte pagina &mdash; klik op een nummer voor uitleg over dat onderdeel</p>
        </div>

        <!-- ==================== MODALS ==================== -->

        <!-- Modal overlay (gedeeld) -->
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

                <!-- Modal 1: Welkomstbericht -->
                <div x-show="openModal === 1"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-blue-600 text-white text-lg font-bold rounded-full">1</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Welkomstbericht</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Bovenaan het dashboard word je persoonlijk begroet met je naam. Daaronder staat een korte beschrijving dat dit je overzichtspagina is voor al je facturatie-activiteiten.
                        </p>
                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-400">1 van 5</span>
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende: Statistiekkaarten
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 2: Statistiekkaarten -->
                <div x-show="openModal === 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-green-600 text-white text-lg font-bold rounded-full">2</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Statistiekkaarten</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Vier kaarten geven je de belangrijkste cijfers in een oogopslag:</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-blue-900 dark:text-blue-100">Openstaand</h4>
                                    <p class="text-xs text-blue-800 dark:text-blue-200">Totaalbedrag van onbetaalde facturen (verzonden + verlopen) en hoeveel dat er zijn.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-800">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-green-900 dark:text-green-100">Deze Maand</h4>
                                    <p class="text-xs text-green-800 dark:text-green-200">Totale omzet en aantal facturen van de huidige maand.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-100 dark:border-purple-800">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-purple-900 dark:text-purple-100">Klanten</h4>
                                    <p class="text-xs text-purple-800 dark:text-purple-200">Totaal aantal klanten. Badge toont nieuwe klanten deze maand.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-sm text-amber-900 dark:text-amber-100">Offertes</h4>
                                    <p class="text-xs text-amber-800 dark:text-amber-200">Actieve offertes: hoeveel concept en hoeveel wachten op goedkeuring.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 1" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">2 van 5</span>
                            <button @click="openModal = 3" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 3: Snelle Acties -->
                <div x-show="openModal === 3"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-purple-600 text-white text-lg font-bold rounded-full">3</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Snelle Acties</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Met deze drie knoppen kun je direct aan de slag zonder door het menu te navigeren:</p>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-blue-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Nieuwe Factuur</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Opent direct het formulier om een nieuwe factuur aan te maken.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-purple-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Nieuwe Offerte</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Maak een offerte die je later kunt omzetten naar een factuur.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-green-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Klanten</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Open je klantenlijst om gegevens te bekijken, bewerken of nieuwe klanten toe te voegen.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">3 van 5</span>
                            <button @click="openModal = 4" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 4: Recente Facturen -->
                <div x-show="openModal === 4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-amber-600 text-white text-lg font-bold rounded-full">4</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Recente Facturen</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Een tabel met je meest recente facturen. Per factuur zie je:</p>

                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white w-1/3">Factuurnummer</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Het unieke nummer, bijv. <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">2026001</code></td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Klant</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">De klantnaam</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Bedrag</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Totaalbedrag inclusief BTW</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Datum</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">De factuurdatum</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Status</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">
                                            <div class="flex flex-wrap gap-1.5 mt-1">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300">Concept</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Verzonden</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Betaald</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Verlopen</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">Geannuleerd</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Bekijken &rarr;</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Link naar de detailpagina van de factuur</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-200">
                                <strong>Tip:</strong> Klik op <span class="font-medium">Bekijk alle &rarr;</span> rechtsboven om naar het volledige facturenoverzicht te gaan met zoek- en filtermogelijkheden.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 3" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">4 van 5</span>
                            <button @click="openModal = 5" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 5: Sidebar Navigatie -->
                <div x-show="openModal === 5"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-red-600 text-white text-lg font-bold rounded-full">5</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Sidebar Navigatie</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Het navigatiemenu links is opgedeeld in twee groepen:</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Hoofdmenu</h4>
                                <div class="space-y-1.5 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Dashboard</strong> &mdash; Deze pagina</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Facturen</strong> &mdash; Alle facturen</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Offertes</strong> &mdash; Offertes beheren</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Klanten</strong> &mdash; Klantenbestand</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Producten</strong> &mdash; Producten/diensten</p>
                                </div>
                            </div>
                            <div>
                                <h4 class="font-semibold text-sm text-gray-900 dark:text-white mb-2">Instellingen</h4>
                                <div class="space-y-1.5 text-sm">
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Bedrijfsgegevens</strong> &mdash; Naam, adres, KVK</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Templates</strong> &mdash; Factuur-ontwerp</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>BTW Tarieven</strong> &mdash; BTW-percentages</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Instellingen</strong> &mdash; App-voorkeuren</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>E-mailverbindingen</strong> &mdash; Google/Microsoft</p>
                                    <p class="text-gray-700 dark:text-gray-300"><strong>Help & Instructies</strong> &mdash; Deze hulppagina's</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <strong>Tip:</strong> Op kleinere schermen kun je de sidebar in- en uitklappen met de hamburger-knop linksboven.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 4" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">5 van 5</span>
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
                    <h4 class="font-medium text-gray-900 dark:text-white">Hoe maak ik mijn eerste factuur aan?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Klik op de blauwe knop <strong>Nieuwe Factuur</strong> bij de Snelle Acties, of ga via het menu naar <strong>Facturen</strong> en klik daar op <strong>+ Nieuwe Factuur</strong>.</p>
                </div>
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Wat betekent de status "Verlopen"?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Een factuur krijgt de status <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Verlopen</span> als de betalingstermijn is verstreken en de factuur nog niet als betaald is gemarkeerd.</p>
                </div>
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Kan ik een offerte omzetten naar een factuur?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ja! Open de offerte via het oog-icoon (Bekijken) en klik in het Acties-blok op <strong>Dupliceer naar Factuur</strong>. Alle gegevens worden automatisch overgenomen.</p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Hoe wijzig ik mijn bedrijfsgegevens op de factuur?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ga naar <strong>Instellingen &rarr; Bedrijfsgegevens</strong> in het menu. Deze worden automatisch op al je facturen getoond.</p>
                </div>
            </div>
        </div>

        <!-- Navigatie onderaan -->
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('help.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Terug naar overzicht
            </a>
            <a href="{{ route('help.show', 'facturen') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Facturen
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</x-app-layout>
