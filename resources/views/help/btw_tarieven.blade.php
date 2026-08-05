<x-app-layout>
@section('title', __('Help: VAT Rates'))
    <div class="space-y-8" x-data="{ openModal: null }">
        <!-- Breadcrumb + titel -->
        <div>
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('help.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Help & Instructies</a></li>
                    <li class="flex items-center"><svg class="w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                    <li class="font-medium text-gray-900 dark:text-white">BTW Tarieven</li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">BTW Tarieven</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Beheer je BTW-percentages die automatisch worden gebruikt bij het aanmaken van facturen en offertes. Klik op de markeringen voor uitleg.</p>
        </div>

        <!-- ==================== VOORBEELD: BTW Tarieven ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="overflow-x-auto pt-4">
                {{-- Nagebouwd voorbeeld: identiek aan de echte BTW Tarieven-pagina --}}
                <div class="space-y-4" style="min-width: 680px;">

                    {{-- Paginakop --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">BTW Tarieven</span>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">Beheer de BTW-tarieven die beschikbaar zijn op facturen en offertes</p>
                        </div>
                        <div class="relative">
                            <span class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Nieuw Tarief
                            </span>
                            <button @click="openModal = 1" class="absolute flex items-center justify-center w-8 h-8 bg-blue-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-blue-600/30 hover:bg-blue-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: -16px; z-index: 10;">1</button>
                        </div>
                    </div>

                    {{-- Tabel --}}
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="relative px-6 py-4">
                                        <span class="inline-flex items-center gap-1">Naam <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/></svg></span>
                                        <button @click="openModal = 3" class="absolute flex items-center justify-center w-8 h-8 bg-purple-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-purple-600/30 hover:bg-purple-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: 50%; margin-top: -16px; right: -12px; z-index: 10;">3</button>
                                    </th>
                                    <th class="px-6 py-4">Percentage</th>
                                    <th class="px-6 py-4">Standaard</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">BTW Hoog</td>
                                    <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">21%</span></td>
                                    <td class="relative px-6 py-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Standaard
                                        </span>
                                        <button @click="openModal = 4" class="absolute flex items-center justify-center w-8 h-8 bg-amber-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-amber-600/30 hover:bg-amber-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; right: -6px; z-index: 10;">4</button>
                                    </td>
                                    <td class="relative px-6 py-4 text-right">
                                        <span class="font-medium text-blue-600 dark:text-blue-500 mr-3">Bewerken</span>
                                        <button @click="openModal = 5" class="absolute flex items-center justify-center w-8 h-8 bg-red-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-red-600/30 hover:bg-red-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; right: 6px; z-index: 10;">5</button>
                                    </td>
                                </tr>
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">BTW Laag</td>
                                    <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">9%</span></td>
                                    <td class="px-6 py-4"><span class="text-xs text-gray-400 dark:text-gray-500">Instellen als standaard</span></td>
                                    <td class="px-6 py-4 text-right"><span class="font-medium text-blue-600 dark:text-blue-500 mr-3">Bewerken</span><span class="font-medium text-red-600 dark:text-red-500">Verwijderen</span></td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">BTW Vrijgesteld</td>
                                    <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">0%</span></td>
                                    <td class="px-6 py-4"><span class="text-xs text-gray-400 dark:text-gray-500">Instellen als standaard</span></td>
                                    <td class="px-6 py-4 text-right"><span class="font-medium text-blue-600 dark:text-blue-500 mr-3">Bewerken</span><span class="font-medium text-red-600 dark:text-red-500">Verwijderen</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Info box (onderaan, zoals op de echte pagina) --}}
                    <div class="relative p-4 bg-blue-50 dark:bg-gray-700 border border-blue-200 dark:border-gray-600 rounded-lg">
                        <button @click="openModal = 2" class="absolute flex items-center justify-center w-8 h-8 bg-green-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-green-600/30 hover:bg-green-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">2</button>
                        <p class="text-sm text-blue-800 dark:text-white">
                            <strong>Tip:</strong> Het standaard tarief wordt automatisch ingevuld bij het aanmaken van nieuwe factuurregels. Je kunt per factuurregel altijd een ander tarief kiezen.
                        </p>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Nagebouwd voorbeeld van de echte pagina &mdash; klik op een nummer voor uitleg over dat onderdeel</p>
        </div>

        <!-- ==================== SECTIE: Tarief bewerken ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tarief bewerken</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Klik op de blauwe tekstlink <strong class="text-blue-600 dark:text-blue-400">Bewerken</strong> rechts van een tarief. Er opent een pop-up <strong class="text-gray-900 dark:text-white">BTW Tarief Bewerken</strong> met twee velden: <strong class="text-gray-900 dark:text-white">Naam</strong> (bijv. "BTW Hoog") en <strong class="text-gray-900 dark:text-white">Percentage</strong>. Pas de waarden aan en klik op <strong class="text-gray-900 dark:text-white">Bijwerken</strong> om op te slaan. Nieuwe tarieven maak je op dezelfde manier aan via <strong class="text-gray-900 dark:text-white">+ Nieuw Tarief</strong> (de knop heet dan <strong class="text-gray-900 dark:text-white">Aanmaken</strong>).
            </p>
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

                <!-- Modal 1: Nieuw Tarief -->
                <div x-show="openModal === 1"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-blue-600 text-white text-lg font-bold rounded-full">1</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Nieuw Tarief</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Met <strong class="text-gray-900 dark:text-white">+ Nieuw Tarief</strong> open je een pop-up om een nieuw BTW-tarief aan te maken. Vul een naam in (bijv. "BTW Hoog") en het percentage (bijv. 21) en klik op <strong class="text-gray-900 dark:text-white">Aanmaken</strong>. Het nieuwe tarief is daarna beschikbaar bij het aanmaken van factuur- en offerteregels.
                        </p>
                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-400">1 van 5</span>
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende: Info box
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 2: Info box -->
                <div x-show="openModal === 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-green-600 text-white text-lg font-bold rounded-full">2</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Info Box</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            De blauwe informatiebox onder de tabel legt uit dat het <strong class="text-gray-900 dark:text-white">standaard BTW-tarief</strong> automatisch wordt ingevuld bij nieuwe factuur- en offerteregels. Dit bespaart tijd doordat je niet bij elke regel handmatig het BTW-percentage hoeft te kiezen. Per regel kun je altijd een ander tarief selecteren.
                        </p>
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

                <!-- Modal 3: Tarieventabel -->
                <div x-show="openModal === 3"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-purple-600 text-white text-lg font-bold rounded-full">3</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Tarieventabel</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">De tabel toont al je aangemaakte BTW-tarieven met naam, percentage en of het tarief standaard is. Klik op een kolomtitel om te sorteren. In Nederland zijn dit de gangbare tarieven:</p>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">21%</span>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Hoog tarief</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Het reguliere BTW-tarief voor de meeste producten en diensten.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">9%</span>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Laag tarief</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Voor o.a. voedingsmiddelen, boeken, medicijnen en culturele diensten.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200">0%</span>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Vrijgesteld</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Voor BTW-vrijgestelde diensten of intracommunautaire leveringen.</p>
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

                <!-- Modal 4: Standaard instellen -->
                <div x-show="openModal === 4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-amber-600 text-white text-lg font-bold rounded-full">4</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Standaard Tarief</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Het tarief met de blauwe badge <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">&check; Standaard</span> wordt automatisch geselecteerd bij nieuwe factuur- en offerteregels. In Nederland is dit meestal 21%.
                        </p>
                        <p class="text-gray-600 dark:text-gray-400">
                            Bij de andere tarieven staat de grijze link <strong class="text-gray-900 dark:text-white">Instellen als standaard</strong>. Klik hierop om dat tarief als standaard in te stellen, bijvoorbeeld als je voornamelijk producten of diensten levert met het verlaagde tarief.
                        </p>

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

                <!-- Modal 5: Bewerken en Verwijderen -->
                <div x-show="openModal === 5"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-red-600 text-white text-lg font-bold rounded-full">5</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Bewerken & Verwijderen</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Rechts van elk tarief staan tekstlinks:</p>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-blue-600 dark:text-blue-500 font-medium text-sm">Bewerken</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Opent een pop-up waarin je de naam of het percentage van het tarief wijzigt. Sla op met <strong>Bijwerken</strong>.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-red-600 dark:text-red-500 font-medium text-sm">Verwijderen</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Verwijdert het tarief na bevestiging. Bij het standaard tarief wordt deze link niet getoond: het standaard tarief kan niet worden verwijderd.</p>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-100 dark:border-amber-800">
                            <p class="text-xs text-amber-800 dark:text-amber-200">
                                <strong>Let op:</strong> Het wijzigen van een tarief heeft geen effect op bestaande facturen. Alleen nieuwe factuurregels gebruiken het gewijzigde percentage.
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
                    <h4 class="font-medium text-gray-900 dark:text-white">Welke BTW-tarieven gelden er in Nederland?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">In Nederland geldt een standaard BTW-tarief van 21%, een verlaagd tarief van 9% (o.a. voor voedsel, boeken, medicijnen) en een nultarief van 0% voor bepaalde vrijgestelde diensten en intracommunautaire leveringen.</p>
                </div>
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Kan ik een eigen BTW-tarief toevoegen?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ja, klik op <strong>+ Nieuw Tarief</strong> en vul een naam en percentage in. Dit kan handig zijn als je te maken hebt met buitenlandse BTW-tarieven.</p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Verandert het BTW-tarief op bestaande facturen als ik het percentage wijzig?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Nee, bestaande facturen behouden het BTW-percentage dat gold op het moment van aanmaken. Alleen nieuwe factuurregels gebruiken het gewijzigde tarief.</p>
                </div>
            </div>
        </div>

        <!-- Navigatie onderaan -->
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('help.show', 'klanten') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Klanten
            </a>
            <a href="{{ route('help.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Terug naar overzicht
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</x-app-layout>
