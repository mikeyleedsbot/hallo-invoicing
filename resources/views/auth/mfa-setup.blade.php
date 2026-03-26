<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
            <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Stel tweestapsverificatie in</h2>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Scan de QR-code met je authenticator-app
        </p>
    </div>

    <!-- App download links -->
    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Nog geen authenticator-app?</p>
        <div class="flex flex-col gap-2">
            <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-3 px-3 py-2 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-400 hover:shadow-sm transition-all group">
                <svg class="w-5 h-5 text-gray-500 group-hover:text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-none">Download in de</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">App Store (iPhone)</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 ml-auto flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-3 px-3 py-2 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-400 hover:shadow-sm transition-all group">
                <svg class="w-5 h-5 text-gray-500 group-hover:text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M3.18 23.76a2 2 0 01-.64-1.51V1.75A2 2 0 012.54.24l.1-.07 12.37 11.84-.1.1L2.54 23.83l-.1-.07zM16.54 13.2l2.88 2.76-3.95 2.2-2.55-2.44 3.62-2.52zm2.88-5.16L16.54 10.8 12.92 8.28l2.55-2.44 3.95 2.2zm-15.9-6.7L17.44 9.1l-2.9 2.78L3.52 1.34zM3.52 22.66l11.02-10.54 2.9 2.78L3.52 22.66z"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-none">Downloaden via</p>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Google Play (Android)</p>
                </div>
                <svg class="w-4 h-4 text-gray-400 ml-auto flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Stappen -->
    <div class="mb-6 space-y-3">
        <div class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold">1</span>
            <p class="text-sm text-gray-700 dark:text-gray-300">Download <strong>Google Authenticator</strong> via de links hierboven</p>
        </div>
        <div class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold">2</span>
            <p class="text-sm text-gray-700 dark:text-gray-300">Scan de QR-code hieronder met de app</p>
        </div>
        <div class="flex items-start gap-3">
            <span class="flex-shrink-0 inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white text-xs font-bold">3</span>
            <p class="text-sm text-gray-700 dark:text-gray-300">Voer de 6-cijferige code in om de instelling te bevestigen</p>
        </div>
    </div>

    <!-- QR Code -->
    <div class="flex justify-center mb-6">
        <div class="p-4 bg-white rounded-xl border border-gray-200 dark:border-gray-600 shadow-sm">
            {!! $qrCodeSvg !!}
        </div>
    </div>

    <!-- Handmatige sleutel -->
    <div class="mb-6">
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1 text-center">Kan de QR-code niet scannen? Voer deze sleutel handmatig in:</p>
        <div class="flex items-center justify-center gap-2">
            <code class="text-sm font-mono bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 px-3 py-1.5 rounded-lg tracking-widest select-all">
                {{ $secret }}
            </code>
        </div>
    </div>

    <!-- Verificatie formulier -->
    <form method="POST" action="{{ route('mfa.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="code" value="Verificatiecode" />
            <x-text-input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                class="mt-1 block w-full text-center text-2xl font-mono tracking-widest"
                placeholder="000000"
                autofocus
                autocomplete="one-time-code"
            />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center">
            MFA bevestigen & activeren
        </x-primary-button>
    </form>
</x-guest-layout>
