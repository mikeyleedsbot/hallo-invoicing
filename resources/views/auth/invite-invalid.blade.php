<x-guest-layout>
    <div class="text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
            <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>

        @if($reason === 'expired')
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Link verlopen</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Deze uitnodigingslink is verlopen (geldig voor 72 uur).<br>
            Neem contact op met je beheerder voor een nieuwe uitnodiging.
        </p>
        @else
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Ongeldige link</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Deze uitnodigingslink is niet geldig of al gebruikt.<br>
            Neem contact op met je beheerder.
        </p>
        @endif

        <a href="{{ route('login') }}" class="mt-6 inline-block text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
            Terug naar inloggen →
        </a>
    </div>
</x-guest-layout>
