<x-app-layout>
    <div class="space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Systeem e-mailinstellingen</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Systeemberichten (registraties, goedkeuringen, uitnodigingen, wachtwoord-reset) worden verstuurd via de standaard Laravel-mailer uit het <code class="text-sm bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">.env</code>-bestand</p>
        </div>

        <!-- Waarvoor is dit -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5">
            <div class="flex gap-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/60 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                    </div>
                </div>
                <div class="flex-1 text-sm">
                    <p class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Waarvoor wordt deze mailer gebruikt?</p>
                    <p class="text-blue-900 dark:text-blue-100 mb-3">
                        De systeem-mailer verstuurt uitsluitend <strong>systeemberichten</strong> van Hallo Invoicing zelf:
                    </p>
                    <ul class="list-disc list-inside space-y-1 text-blue-900 dark:text-blue-100 mb-3 ml-1">
                        <li>Uitnodigingsmails voor nieuwe gebruikers</li>
                        <li>Notificatie naar admin bij een nieuwe registratie-aanvraag</li>
                        <li>Bevestiging "account goedgekeurd" of "account afgewezen" aan de aanvrager</li>
                        <li>Wachtwoord-reset mails</li>
                    </ul>
                    <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-800">
                        <p class="text-blue-900 dark:text-blue-100 font-semibold mb-1">Niet voor facturen en offertes</p>
                        <p class="text-blue-900 dark:text-blue-100">
                            Factuur- en offertemails worden verstuurd vanaf het eigen Gmail- of Microsoft 365-account van de gebruiker. Gebruikers koppelen dat zelf via
                            <a href="{{ route('mail-connections.index') }}" class="underline font-medium hover:text-blue-700 dark:hover:text-white">E-mailverbindingen</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="flex items-center p-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="flex items-center p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50">
            <ul class="list-disc list-inside space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <!-- Status kaart -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="flex items-center gap-4">
                @if($mailConfig['mailer'] !== 'log')
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Systeem-mailer actief ({{ $mailConfig['mailer'] }})</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Registratie-, goedkeurings- en reset-mails worden echt verstuurd</p>
                </div>
                @else
                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">Mailer staat op "log"</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Systeemmails worden alleen naar het logbestand geschreven en niet echt verstuurd. Zet <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">MAIL_MAILER=smtp</code> in .env met de SMTP-gegevens van je provider.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Huidige configuratie (read-only) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Huidige configuratie (uit .env)</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Wijzigen doe je in het <code class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">.env</code>-bestand op de server; deze pagina toont alleen de actieve waarden</p>
            </div>
            <div class="p-6">
                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-600">
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                            <tr class="bg-white dark:bg-gray-800">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white w-1/3"><code class="text-xs">MAIL_MAILER</code></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $mailConfig['mailer'] ?: '—' }}</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white"><code class="text-xs">MAIL_HOST</code></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $mailConfig['host'] ?: '—' }}</td>
                            </tr>
                            <tr class="bg-white dark:bg-gray-800">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white"><code class="text-xs">MAIL_PORT</code></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $mailConfig['port'] ?: '—' }}</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white"><code class="text-xs">MAIL_USERNAME</code></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $mailConfig['username'] ?: '—' }}</td>
                            </tr>
                            <tr class="bg-white dark:bg-gray-800">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white"><code class="text-xs">MAIL_FROM_ADDRESS</code></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $mailConfig['from_address'] ?: '—' }}</td>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-white"><code class="text-xs">MAIL_FROM_NAME</code></td>
                                <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $mailConfig['from_name'] ?: '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Testmail -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Verbinding testen</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Stuur een testmail om te verifiëren dat de configuratie correct is</p>
            </div>
            <form action="{{ route('email-settings.test') }}" method="POST" class="p-6">
                @csrf
                <div class="flex gap-3">
                    <input type="email" name="test_email" value="{{ Auth::user()->email }}" required
                           placeholder="testadres@voorbeeld.nl"
                           class="flex-1 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-gray-800 hover:bg-gray-900 rounded-lg transition-colors dark:bg-gray-700 dark:hover:bg-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Testmail sturen
                    </button>
                </div>
                @error('test_email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </form>
        </div>

    </div>
</x-app-layout>
