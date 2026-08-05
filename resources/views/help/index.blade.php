<x-app-layout>
@section('title', __('Help & Instructions'))
    <div class="space-y-6">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Help & Instructies</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Leer hoe je Hallo Invoicing optimaal gebruikt. Klik op een onderwerp voor een uitgebreide uitleg.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Dashboard -->
            <a href="{{ route('help.show', 'dashboard') }}" class="group block bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-md hover:border-blue-200 dark:hover:border-blue-700 transition-all">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 mb-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 22 21">
                        <path d="M16.975 11H10V4.025a1 1 0 0 0-1.066-.998 8.5 8.5 0 1 0 9.039 9.039.999.999 0 0 0-1-1.066h.002Z"/>
                        <path d="M12.5 0c-.157 0-.311.01-.565.027A1 1 0 0 0 11 1.02V10h8.975a1 1 0 0 0 1-.935c.013-.188.028-.374.028-.565A8.51 8.51 0 0 0 12.5 0Z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">Dashboard</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Overzicht van je facturatie, statistieken, snelle acties en recente facturen.</p>
            </a>

            <!-- Facturen -->
            <a href="{{ route('help.show', 'facturen') }}" class="group block bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-md hover:border-green-200 dark:hover:border-green-700 transition-all">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-green-500 to-green-600 mb-4">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 2a2 2 0 00-2 2v8a2 2 0 002 2h6a2 2 0 002-2V6.414A2 2 0 0016.414 5L13 1.586A2 2 0 0011.586 1H9zm3 4a1 1 0 10-2 0v1H9a1 1 0 100 2h1v1a1 1 0 102 0V9h1a1 1 0 100-2h-1V6z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors">Facturen</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Facturen aanmaken, versturen, en beheren.</p>
            </a>

            <!-- Offertes -->
            <a href="{{ route('help.show', 'offertes') }}" class="group block bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-md hover:border-purple-200 dark:hover:border-purple-700 transition-all">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">Offertes</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Offertes opstellen en omzetten naar facturen.</p>
            </a>

            <!-- Klanten -->
            <a href="{{ route('help.show', 'klanten') }}" class="group block bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-md hover:border-amber-200 dark:hover:border-amber-700 transition-all">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">Klanten</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Klanten toevoegen, bewerken en beheren.</p>
            </a>

            <!-- BTW Tarieven -->
            <a href="{{ route('help.show', 'btw-tarieven') }}" class="group block bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-md hover:border-red-200 dark:hover:border-red-700 transition-all">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-red-500 to-red-600 mb-4">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">BTW Tarieven</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">BTW-percentages instellen en beheren.</p>
            </a>
        </div>
    </div>
</x-app-layout>
