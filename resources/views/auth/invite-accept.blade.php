<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Welkom, {{ $user->name }}! 👋</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Stel een wachtwoord in om je account te activeren
        </p>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    <!-- Account info -->
    <div class="mb-5 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $user->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                @if($user->company_name)
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->company_name }}</p>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('invite.activate', $token) }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" value="Wachtwoord kiezen" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimaal 8 tekens</p>
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Wachtwoord bevestigen" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
        </div>

        <!-- MFA info -->
        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-xs text-blue-800 dark:text-blue-300">
                <strong>Volgende stap:</strong> Na het activeren word je gevraagd tweestapsverificatie (MFA) in te stellen voor extra beveiliging.
            </p>
        </div>

        <x-primary-button class="w-full justify-center">
            Account activeren →
        </x-primary-button>
    </form>
</x-guest-layout>
