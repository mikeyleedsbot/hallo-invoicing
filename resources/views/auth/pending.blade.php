<x-guest-layout>
    <div class="text-center py-6">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-6">
            <svg class="h-8 w-8 text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Aanvraag ontvangen!</h2>

        @if (session('success'))
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                {{ session('success') }}
            </p>
        @else
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                Bedankt voor je aanmelding. Een beheerder bekijkt je aanvraag en stuurt je een e-mail zodra je account is goedgekeurd.
            </p>
        @endif

        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/30 rounded-lg border border-blue-200 dark:border-blue-800">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <strong>Wat gebeurt er nu?</strong><br>
                Zodra je aanvraag is goedgekeurd ontvang je een e-mail met een link om in te loggen.
            </p>
        </div>

        <div class="mt-6">
            <a href="{{ route('login') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                Terug naar inloggen →
            </a>
        </div>
    </div>
</x-guest-layout>
