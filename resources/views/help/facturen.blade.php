<x-app-layout>
    <div class="space-y-8 max-w-5xl" x-data="{ openModal: null }">
        <!-- Breadcrumb + titel -->
        <div>
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                    <li><a href="{{ route('help.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400">Help & Instructies</a></li>
                    <li class="flex items-center"><svg class="w-4 h-4 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg></li>
                    <li class="font-medium text-gray-900 dark:text-white">Facturen</li>
                </ol>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Facturen</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Bekijk, filter en beheer al je facturen. Klik op de genummerde markeringen voor uitleg over elk onderdeel.</p>
        </div>

        <!-- ==================== VOORBEELD 1: Facturenoverzicht ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Facturenoverzicht</h2>
            <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 p-4 overflow-x-auto">
                {{-- Nagebouwd voorbeeld: identiek aan de echte Facturen-pagina --}}
                <div class="space-y-4" style="min-width: 780px;">

                    {{-- Paginakop --}}
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Facturen
                            </span>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Beheer al je facturen</p>
                        </div>
                        <div class="relative">
                            <span class="text-white bg-gradient-to-r from-blue-500 to-blue-700 font-medium rounded-lg text-sm px-5 py-2.5 inline-flex items-center gap-2 shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Nieuwe Factuur
                            </span>
                            <button @click="openModal = 1" class="absolute flex items-center justify-center w-8 h-8 bg-blue-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-blue-600/30 hover:bg-blue-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: -16px; z-index: 10;">1</button>
                        </div>
                    </div>

                    {{-- Filterbalk --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                        <button @click="openModal = 2" class="absolute flex items-center justify-center w-8 h-8 bg-green-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-green-600/30 hover:bg-green-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">2</button>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">Zoek factuur...</div>
                            <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white flex items-center justify-between">Alle statussen <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                            <div class="bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">dd-mm-jjjj</div>
                            <div class="bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">dd-mm-jjjj</div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <span class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg">Filteren</span>
                        </div>
                    </div>

                    {{-- Tabel --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                <tr>
                                    <th class="relative px-6 py-3">
                                        <span class="inline-flex items-center gap-1">Factuurnummer <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/></svg></span>
                                        <button @click="openModal = 3" class="absolute flex items-center justify-center w-8 h-8 bg-purple-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-purple-600/30 hover:bg-purple-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; right: -12px; z-index: 10;">3</button>
                                    </th>
                                    <th class="px-6 py-3">Klant</th>
                                    <th class="px-6 py-3">Datum</th>
                                    <th class="px-6 py-3">Vervaldatum</th>
                                    <th class="px-6 py-3">Bedrag</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Acties</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">2026001</td>
                                    <td class="px-6 py-4">Bakkerij De Gouden Korst</td>
                                    <td class="px-6 py-4">01-07-2026</td>
                                    <td class="px-6 py-4">15-07-2026</td>
                                    <td class="px-6 py-4 font-medium">€ 1.250,00</td>
                                    <td class="relative px-6 py-4">
                                        <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Verzonden</span>
                                        <button @click="openModal = 4" class="absolute flex items-center justify-center w-8 h-8 bg-amber-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-amber-600/30 hover:bg-amber-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; right: -6px; z-index: 10;">4</button>
                                    </td>
                                    <td class="relative px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <span class="text-purple-600 dark:text-purple-500" title="Download PDF"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg></span>
                                            <span class="text-gray-600 dark:text-gray-400" title="Afdrukken"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg></span>
                                            <span class="text-green-600 dark:text-green-500" title="E-mail versturen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></span>
                                            <span class="text-blue-600 dark:text-blue-500" title="Bekijken"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></span>
                                            <span class="text-green-600 dark:text-green-400" title="Bewerken"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></span>
                                            <span class="text-red-600 dark:text-red-500" title="Verwijderen"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></span>
                                        </div>
                                        <button @click="openModal = 5" class="absolute flex items-center justify-center w-8 h-8 bg-red-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-red-600/30 hover:bg-red-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; right: 6px; z-index: 10;">5</button>
                                    </td>
                                </tr>
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">2026002</td>
                                    <td class="px-6 py-4">Jansen &amp; Zonen B.V.</td>
                                    <td class="px-6 py-4">25-06-2026</td>
                                    <td class="px-6 py-4">09-07-2026</td>
                                    <td class="px-6 py-4 font-medium">€ 3.450,00</td>
                                    <td class="px-6 py-4"><span class="text-xs font-medium px-2.5 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Betaald</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <span class="text-purple-600 dark:text-purple-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg></span>
                                            <span class="text-gray-600 dark:text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg></span>
                                            <span class="text-gray-300 dark:text-gray-600" title="Klant heeft geen e-mailadres"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></span>
                                            <span class="text-blue-600 dark:text-blue-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></span>
                                            <span class="text-green-600 dark:text-green-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></span>
                                            <span class="text-red-600 dark:text-red-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="bg-white dark:bg-gray-800">
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">2026003</td>
                                    <td class="px-6 py-4">IT Solutions Groningen</td>
                                    <td class="px-6 py-4">10-06-2026</td>
                                    <td class="px-6 py-4">24-06-2026</td>
                                    <td class="px-6 py-4 font-medium">€ 950,00</td>
                                    <td class="px-6 py-4"><span class="text-xs font-medium px-2.5 py-0.5 rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Concept</span></td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <span class="text-purple-600 dark:text-purple-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path></svg></span>
                                            <span class="text-gray-600 dark:text-gray-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg></span>
                                            <span class="text-green-600 dark:text-green-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg></span>
                                            <span class="text-blue-600 dark:text-blue-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg></span>
                                            <span class="text-green-600 dark:text-green-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></span>
                                            <span class="text-red-600 dark:text-red-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Nagebouwd voorbeeld van de echte pagina &mdash; klik op een nummer voor uitleg over dat onderdeel</p>
        </div>

        <!-- ==================== VOORBEELD 2: Factuur aanmaken ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Factuur aanmaken</h2>
            <div class="rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 p-4 overflow-x-auto">
                <div class="space-y-4" style="min-width: 700px;">

                    {{-- Paginakop --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Nieuwe Factuur
                            </span>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Maak een nieuwe factuur aan voor een klant</p>
                        </div>
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </div>

                    {{-- Totaalbalk --}}
                    <div class="relative bg-gradient-to-br from-blue-50 to-blue-100 dark:from-gray-800 dark:to-gray-700 rounded-lg shadow-sm border border-blue-200 dark:border-gray-600 p-6">
                        <button @click="openModal = 6" class="absolute flex items-center justify-center w-8 h-8 bg-blue-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-blue-600/30 hover:bg-blue-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">6</button>
                        <div class="grid grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Subtotaal</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">€ 1.000,00</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">BTW</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-white">€ 210,00</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Totaal</div>
                                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">€ 1.210,00</div>
                            </div>
                        </div>
                    </div>

                    {{-- Factuurgegevens --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <button @click="openModal = 7" class="absolute flex items-center justify-center w-8 h-8 bg-green-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-green-600/30 hover:bg-green-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">7</button>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Factuurgegevens</h3>
                        <div class="grid grid-cols-4 gap-4 mb-4">
                            <div>
                                <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Factuurnummer <span class="text-red-500">*</span></span>
                                <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">2026004</div>
                            </div>
                            <div>
                                <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Klant <span class="text-red-500">*</span></span>
                                <div class="bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">Zoek klant...</div>
                            </div>
                            <div>
                                <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Factuurdatum <span class="text-red-500">*</span></span>
                                <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">02-07-2026</div>
                            </div>
                            <div>
                                <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Vervaldatum <span class="text-red-500">*</span></span>
                                <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">16-07-2026</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Template</span>
                                <div class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white flex items-center justify-between">Standaard template <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg></div>
                            </div>
                        </div>
                    </div>

                    {{-- Factuurregels --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <button @click="openModal = 8" class="absolute flex items-center justify-center w-8 h-8 bg-purple-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-purple-600/30 hover:bg-purple-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">8</button>
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Factuurregels</h3>
                            <div class="flex gap-2">
                                <span class="text-white bg-blue-600 font-medium rounded-lg text-sm px-4 py-2 inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                    Product toevoegen
                                </span>
                                <span class="text-white bg-green-600 font-medium rounded-lg text-sm px-4 py-2 inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Regel toevoegen
                                </span>
                            </div>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 bg-gray-50 dark:bg-gray-700">
                            <div class="flex gap-3 items-center">
                                <div class="flex-1 bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white">Website ontwikkeling</div>
                                <div class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" style="width: 6rem;">10</div>
                                <div class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" style="width: 7rem;">€ 100,00</div>
                                <div class="bg-white border border-gray-300 text-gray-900 text-sm rounded-lg p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:text-white" style="width: 5rem;">21%</div>
                                <span class="p-2.5 text-red-600 border border-red-600 dark:border-red-500 rounded-lg inline-flex items-center justify-center"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></span>
                            </div>
                            <div class="mt-2 pt-2 border-t border-gray-300 dark:border-gray-600 text-right">
                                <span class="text-xs text-gray-600 dark:text-gray-400">Regeltotaal: </span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white">€ 1.000,00</span>
                            </div>
                        </div>
                    </div>

                    {{-- BTW verlegd + Opmerkingen + knoppen --}}
                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <button @click="openModal = 9" class="absolute flex items-center justify-center w-8 h-8 bg-amber-600 text-white text-sm font-bold rounded-full shadow-lg ring-4 ring-amber-600/30 hover:bg-amber-700 hover:scale-110 transition-all cursor-pointer animate-pulse hover:animate-none" style="top: -16px; left: 50%; margin-left: -16px; z-index: 10;">9</button>
                        <div class="flex items-start gap-3 mb-4">
                            <span class="mt-1 w-4 h-4 bg-gray-100 border border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600 inline-block"></span>
                            <div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">BTW verlegd</span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Alle regels worden automatisch op 0% gezet en er wordt een opmerking toegevoegd met het BTW-nummer van de klant.</p>
                            </div>
                        </div>
                        <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Opmerkingen</span>
                        <div class="bg-gray-50 border border-gray-300 text-gray-400 text-sm rounded-lg p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400 mb-4" style="min-height: 4.5rem;">Extra opmerkingen of voorwaarden...</div>
                        <div class="flex justify-end gap-3">
                            <span class="text-gray-900 bg-white border border-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-700 dark:text-white dark:border-gray-600">Annuleren</span>
                            <span class="text-white bg-blue-700 dark:bg-blue-600 font-medium rounded-lg text-sm px-5 py-2.5">Factuur opslaan</span>
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 text-center">Nagebouwd voorbeeld van de echte pagina &mdash; klik op een nummer voor uitleg over dat onderdeel</p>
        </div>

        <!-- ==================== SECTIE: Factuur bewerken & status wijzigen ==================== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Factuur bewerken &amp; status wijzigen</h2>

            <div class="space-y-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm text-gray-900 dark:text-white">Gegevens bewerken (potlood-icoon)</h4>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Klik op het groene potlood-icoon in het overzicht om het bewerkformulier te openen. Hier pas je alle gegevens aan: factuurnummer, klant, datums, template, factuurregels, BTW verlegd en opmerkingen. Klik daarna op <strong class="text-gray-900 dark:text-white">Wijzigingen opslaan</strong>. De <strong class="text-gray-900 dark:text-white">status</strong> van de factuur kun je hier <em>niet</em> wijzigen &mdash; dat doe je via de detailpagina (zie hieronder).</p>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm text-blue-900 dark:text-blue-100">Status wijzigen (oog-icoon &rarr; Acties)</h4>
                            <p class="mt-1 text-sm text-blue-800 dark:text-blue-200">Klik op het blauwe oog-icoon om de detailpagina van de factuur te openen. Rechts vind je het blok <strong>Acties</strong> met deze knoppen:</p>
                            <ul class="mt-2 space-y-1.5 text-sm text-blue-800 dark:text-blue-200">
                                <li><strong>Markeer als Verzonden</strong> &mdash; zet de status op <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Verzonden</span>; je kiest daarbij de verzenddatum.</li>
                                <li><strong>Markeer als Betaald</strong> &mdash; zet de status op <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Betaald</span>; je kiest daarbij de betaaldatum.</li>
                                <li><strong>Dupliceer Factuur</strong> &mdash; maakt een kopie met een nieuw factuurnummer (status Concept).</li>
                                <li><strong>Verwijder Factuur</strong> &mdash; verwijdert de factuur na bevestiging.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
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

                <!-- Modal 1: Nieuwe Factuur knop -->
                <div x-show="openModal === 1"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-blue-600 text-white text-lg font-bold rounded-full">1</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Nieuwe Factuur</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            Met de blauwe knop <strong class="text-gray-900 dark:text-white">+ Nieuwe Factuur</strong> rechtsboven open je direct het formulier om een nieuwe factuur aan te maken. Het factuurnummer wordt automatisch gegenereerd op basis van je instellingen.
                        </p>
                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-400">1 van 9</span>
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende: Zoeken & filteren
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 2: Zoeken en filteren -->
                <div x-show="openModal === 2"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-green-600 text-white text-lg font-bold rounded-full">2</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Zoeken & Filteren</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">De filterbalk biedt meerdere manieren om snel de juiste factuur te vinden:</p>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Zoekveld</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Zoek op factuurnummer of klantnaam.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Statusfilter</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Filter op status: Alle statussen, Concept, Verzonden, Betaald, Verlopen of Geannuleerd.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Datumbereik</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Selecteer een begin- en einddatum om facturen uit een bepaalde periode te tonen.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-200">
                                <strong>Tip:</strong> Klik op <strong>Filteren</strong> om de filters toe te passen. Zodra er een filter actief is, verschijnt de knop <strong>Wissen</strong> om alle filters te resetten.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 1" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">2 van 9</span>
                            <button @click="openModal = 3" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 3: Tabelkolommen -->
                <div x-show="openModal === 3"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-purple-600 text-white text-lg font-bold rounded-full">3</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Tabelkolommen</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">De factuurlijst toont de volgende kolommen. Klik op een kolomtitel om te sorteren:</p>

                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white w-1/3">Factuurnummer</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Uniek nummer, bijv. <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">2026001</code></td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Klant</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Naam van de klant</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Datum</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">De factuurdatum</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Vervaldatum</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Uiterste betaaldatum</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Bedrag</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Totaalbedrag inclusief BTW</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Status</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Huidige status van de factuur</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Acties</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Zes icoon-knoppen voor snelle acties (zie markering 5)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-200">
                                <strong>Tip:</strong> Klik op een kolomtitel om de lijst oplopend of aflopend te sorteren. Het blauwe pijltje toont de actieve sortering.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 2" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">3 van 9</span>
                            <button @click="openModal = 4" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 4: Statusbadges -->
                <div x-show="openModal === 4"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-amber-600 text-white text-lg font-bold rounded-full">4</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Statusbadges</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Elke factuur heeft een gekleurde statusbadge:</p>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-300">Concept</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">De factuur is aangemaakt maar nog niet verstuurd.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Verzonden</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">De factuur is verstuurd naar de klant en wacht op betaling.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Betaald</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">De factuur is betaald door de klant.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Verlopen</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">De vervaldatum is verstreken en de factuur is nog niet betaald.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Geannuleerd</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">De factuur is geannuleerd en telt niet meer mee.</p>
                            </div>
                        </div>

                        <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-200">
                                <strong>Status wijzigen?</strong> Open de factuur via het oog-icoon (Bekijken) en gebruik de knoppen <strong>Markeer als Verzonden</strong> of <strong>Markeer als Betaald</strong> in het Acties-blok.
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 3" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">4 van 9</span>
                            <button @click="openModal = 5" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 5: Actieknoppen -->
                <div x-show="openModal === 5"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-red-600 text-white text-lg font-bold rounded-full">5</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Actieknoppen</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Rechts van elke factuur staan zes icoon-knoppen, in deze volgorde:</p>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Download PDF <span class="font-normal text-gray-500 dark:text-gray-400">(paars)</span></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Download de factuur als PDF-bestand.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Afdrukken <span class="font-normal text-gray-500 dark:text-gray-400">(grijs)</span></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Opent een printvriendelijke versie in een nieuw tabblad.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">E-mail versturen <span class="font-normal text-gray-500 dark:text-gray-400">(groen)</span></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Download de PDF en opent je e-mailprogramma met een vooringevulde mail naar de klant. Grijs weergegeven als de klant geen e-mailadres heeft.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Bekijken <span class="font-normal text-gray-500 dark:text-gray-400">(blauw)</span></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Opent de detailpagina. Hier wijzig je ook de status (Markeer als Verzonden/Betaald) en kun je dupliceren of verwijderen.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Bewerken <span class="font-normal text-gray-500 dark:text-gray-400">(groen potlood)</span></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Opent het bewerkformulier om de factuurgegevens en -regels aan te passen.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </div>
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Verwijderen <span class="font-normal text-gray-500 dark:text-gray-400">(rood)</span></span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Verwijdert de factuur. Er wordt eerst om bevestiging gevraagd.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 4" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">5 van 9</span>
                            <button @click="openModal = 6" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende: Factuur aanmaken
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 6: Totaalbalk -->
                <div x-show="openModal === 6"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-blue-600 text-white text-lg font-bold rounded-full">6</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Totaalbalk</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            De blauwe balk bovenaan toont real-time de totalen van je factuur terwijl je regels toevoegt of wijzigt:
                        </p>

                        <div class="grid grid-cols-3 gap-3">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800 text-center">
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-300">Subtotaal</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-100">Excl. BTW</p>
                            </div>
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800 text-center">
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-300">BTW</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-100">BTW bedrag</p>
                            </div>
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800 text-center">
                                <p class="text-xs font-medium text-blue-600 dark:text-blue-300">Totaal</p>
                                <p class="text-lg font-bold text-blue-900 dark:text-blue-100">Incl. BTW</p>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 5" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">6 van 9</span>
                            <button @click="openModal = 7" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 7: Factuurgegevens -->
                <div x-show="openModal === 7"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-green-600 text-white text-lg font-bold rounded-full">7</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Factuurgegevens</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">De basisgegevens van je factuur. Velden met een * zijn verplicht:</p>

                        <div class="space-y-3">
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Factuurnummer *</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Wordt automatisch gegenereerd. Je kunt het aanpassen als dat nodig is.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Klant *</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Zoek en selecteer een bestaande klant uit de lijst. Bestaat de klant nog niet? Maak deze dan eerst aan via <strong>Klanten &rarr; + Nieuwe Klant</strong>.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Factuurdatum *</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Standaard vandaag. Pas aan als je met terugwerkende kracht factureert.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Vervaldatum *</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Standaard 14 dagen na de factuurdatum. Je kunt deze per factuur aanpassen.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div>
                                    <span class="font-semibold text-sm text-gray-900 dark:text-white">Template</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kies het ontwerp voor de factuur-PDF. Standaard wordt je standaard-template gebruikt (beheer via <strong>Instellingen &rarr; Templates</strong>).</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 6" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">7 van 9</span>
                            <button @click="openModal = 8" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 8: Factuurregels -->
                <div x-show="openModal === 8"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-xl w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-purple-600 text-white text-lg font-bold rounded-full">8</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Factuurregels</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">Hier voeg je de producten of diensten toe die je factureert. Elke regel bevat:</p>

                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white w-1/3">Omschrijving</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Beschrijving van het product of de dienst</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Aantal</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Hoeveelheid (bijv. uren of stuks)</td>
                                    </tr>
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">Prijs/stuk</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Prijs per eenheid excl. BTW</td>
                                    </tr>
                                    <tr class="bg-gray-50 dark:bg-gray-700/50">
                                        <td class="px-3 py-2 font-medium text-gray-900 dark:text-white">BTW %</td>
                                        <td class="px-3 py-2 text-gray-600 dark:text-gray-400">Het BTW-tarief (standaard je standaard-tarief, meestal 21%)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 space-y-2">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800">
                                <p class="text-xs text-blue-800 dark:text-blue-200">
                                    <strong>Product toevoegen</strong> (blauwe knop): kies een eerder aangemaakt product uit de lijst. De omschrijving en prijs worden automatisch ingevuld.
                                </p>
                            </div>
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-100 dark:border-green-800">
                                <p class="text-xs text-green-800 dark:text-green-200">
                                    <strong>+ Regel toevoegen</strong> (groene knop): voegt een lege regel toe die je handmatig invult. Verwijder een regel met het rode prullenbak-icoon rechts van de regel (minimaal 1 regel vereist).
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 7" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">8 van 9</span>
                            <button @click="openModal = 9" class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                Volgende
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal 9: BTW verlegd, opmerkingen & opslaan -->
                <div x-show="openModal === 9"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex-shrink-0 flex items-center justify-center w-10 h-10 bg-amber-600 text-white text-lg font-bold rounded-full">9</span>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">BTW verlegd, Opmerkingen & Opslaan</h3>
                            <button @click="openModal = null" class="ml-auto text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            Met het vinkje <strong class="text-gray-900 dark:text-white">BTW verlegd</strong> worden alle regels op 0% gezet en wordt het BTW-nummer van de klant op de factuur vermeld. De klant moet daarvoor wel een BTW-nummer hebben.
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            In het tekstveld <strong class="text-gray-900 dark:text-white">Opmerkingen</strong> voeg je extra informatie toe die op de factuur verschijnt, zoals betalingsvoorwaarden of een persoonlijk bericht.
                        </p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="px-3 py-1.5 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded">Annuleren</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Terug naar het overzicht zonder op te slaan.</p>
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <span class="px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded">Factuur opslaan</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Slaat de factuur op als concept. Je kunt deze later nog bewerken.</p>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="openModal = 8" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                Vorige
                            </button>
                            <span class="text-xs text-gray-400">9 van 9</span>
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
                    <h4 class="font-medium text-gray-900 dark:text-white">Hoe stuur ik een factuur per e-mail?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Klik op het groene e-mail-icoon rechts van de factuur in het overzicht. De factuur wordt als PDF gedownload en je e-mailprogramma opent met een vooringevulde mail; voeg de PDF als bijlage toe. Stel via <strong>Instellingen &rarr; E-mailverbindingen</strong> een Google Workspace- of Microsoft 365-account in om facturen met &eacute;&eacute;n klik te versturen.</p>
                </div>
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Hoe markeer ik een factuur als betaald of verzonden?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Open de factuur via het blauwe oog-icoon (Bekijken) en klik in het Acties-blok op <strong>Markeer als Verzonden</strong> of <strong>Markeer als Betaald</strong>. Je kiest daarbij de verzend- of betaaldatum. Dit kan niet via het bewerkformulier.</p>
                </div>
                <div class="border-b border-gray-100 dark:border-gray-700 pb-4">
                    <h4 class="font-medium text-gray-900 dark:text-white">Kan ik het factuurnummer-formaat aanpassen?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ja, ga naar <strong>Instellingen</strong> om het prefix en de startnummering van je facturen te wijzigen. Bijvoorbeeld: <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">FACT-2026001</code>.</p>
                </div>
                <div>
                    <h4 class="font-medium text-gray-900 dark:text-white">Wat als ik een factuur wil crediteren?</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Open de factuur via het oog-icoon en klik op <strong>Dupliceer Factuur</strong>. Pas in de kopie de bedragen aan naar negatieve bedragen en verwijs in de opmerkingen naar het originele factuurnummer.</p>
                </div>
            </div>
        </div>

        <!-- Navigatie onderaan -->
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('help.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Terug naar overzicht
            </a>
            <a href="{{ route('help.show', 'offertes') }}" class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                Offertes
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</x-app-layout>
