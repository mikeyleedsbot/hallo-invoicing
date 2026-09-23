<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6 3.87V15a4 4 0 00-3-3.87M9 11.13A4 4 0 106 4a4 4 0 003 7.13z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Je bent uitgenodigd voor het team! 👋</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ $invitation->inviter->name }} heeft je uitgenodigd om samen te werken in {{ $invitation->team->name }}
        </p>
    </div>

    @if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    @if($existingUser)
    {{-- Bestaand account: geen wachtwoord nodig, alleen bevestigen --}}
    <div class="mb-5 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                {{ strtoupper(substr($existingUser->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900 dark:text-white text-sm">{{ $existingUser->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $existingUser->email }}</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('team-invitations.activate', $token) }}">
        @csrf
        <x-primary-button class="w-full justify-center">
            Uitnodiging accepteren →
        </x-primary-button>
    </form>
    @else
    {{-- Nieuw account: naam + wachtwoord instellen --}}
    <form method="POST" action="{{ route('team-invitations.activate', $token) }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Je naam" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" autofocus required />
        </div>

        <div>
            <x-input-label value="E-mailadres" />
            <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $invitation->email }}</p>
        </div>

        <div>
            <x-input-label for="password" value="Wachtwoord kiezen" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Minimaal 8 tekens</p>
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Wachtwoord bevestigen" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
        </div>

        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-xs text-blue-800 dark:text-blue-100">
                <strong>Volgende stap:</strong> Na het activeren word je gevraagd tweestapsverificatie (MFA) in te stellen voor extra beveiliging.
            </p>
        </div>

        <x-primary-button class="w-full justify-center">
            Account activeren →
        </x-primary-button>
    </form>
    @endif
</x-guest-layout>
