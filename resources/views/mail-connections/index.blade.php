<x-app-layout>
    <div class="space-y-6"
         x-data="{
             showGoogleSetup: {{ $googleConfigured ? 'false' : 'true' }},
             showMicrosoftSetup: {{ $microsoftConfigured ? 'false' : 'true' }},
             showGoogleSecret: false,
             showMicrosoftSecret: false,
             copied: '',
             copy(text, key) {
                 navigator.clipboard.writeText(text).then(() => {
                     this.copied = key;
                     setTimeout(() => { if (this.copied === key) this.copied = ''; }, 2000);
                 });
             }
         }">

        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">E-mailverbindingen</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Koppel je Google Workspace of Microsoft 365 account om facturen rechtstreeks vanuit Hallo Invoicing te versturen — vanaf je eigen mailadres.
            </p>
        </div>

        @if(session('success'))
        <div class="p-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800">
            {{ session('success') }}
        </div>
        @endif

        @if(session('warning'))
        <div class="p-4 text-sm text-amber-800 border border-amber-300 rounded-lg bg-amber-50 dark:bg-gray-800 dark:text-amber-400 dark:border-amber-800">
            {{ session('warning') }}
        </div>
        @endif

        @if($errors->any())
        <div class="p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Bestaande verbindingen --}}
        @if($accounts->count())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Verbonden accounts</h2>
            </div>
            <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($accounts as $account)
                <li class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-10 h-10 rounded-lg {{ $account->isGoogle() ? 'bg-red-50 dark:bg-red-900/20' : 'bg-blue-50 dark:bg-blue-900/20' }}">
                            @if($account->isGoogle())
                                <svg class="w-6 h-6" viewBox="0 0 48 48"><path fill="#fbc02d" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.6-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.9 1.1 8 3.1l5.7-5.7C34.3 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 20-8 20-20 0-1.3-.1-2.3-.4-3.5z"/><path fill="#e53935" d="M6.3 14.7l6.6 4.8C14.6 16 18.9 13 24 13c3 0 5.9 1.1 8 3.1l5.7-5.7C34.3 7.1 29.4 5 24 5 16.3 5 9.7 9 6.3 14.7z"/><path fill="#4caf50" d="M24 44c5.3 0 10.1-2 13.7-5.3l-6.3-5.2c-2 1.4-4.5 2.3-7.4 2.3-5.3 0-9.7-3.4-11.3-8l-6.5 5C9.5 40 16.2 44 24 44z"/><path fill="#1565c0" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.3-4 5.7l6.3 5.2C41.6 36 44 31 44 24c0-1.3-.1-2.3-.4-3.5z"/></svg>
                            @else
                                <svg class="w-6 h-6" viewBox="0 0 23 23"><path fill="#f35325" d="M1 1h10v10H1z"/><path fill="#81bc06" d="M12 1h10v10H12z"/><path fill="#05a6f0" d="M1 12h10v10H1z"/><path fill="#ffba08" d="M12 12h10v10H12z"/></svg>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $account->from_email }}</p>
                                @if($account->is_default)
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Standaard</span>
                                @endif
                                @if($account->isExpired())
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Token verlopen</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $account->providerLabel() }}{{ $account->from_name ? ' — ' . $account->from_name : '' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @unless($account->is_default)
                        <form method="POST" action="{{ route('mail-connections.set-default', $account) }}">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                                Maak standaard
                            </button>
                        </form>
                        @endunless
                        <form method="POST" action="{{ route('mail-connections.destroy', $account) }}"
                              onsubmit="return confirm('Verbinding met {{ $account->from_email }} verbreken?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg dark:bg-red-900/20 dark:text-red-400">
                                Verbreken
                            </button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Toelichting --}}
        <div class="p-4 text-sm text-blue-900 dark:text-blue-200 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <strong>Hoe werkt dit?</strong> Elke gebruiker stelt zijn eigen OAuth-app in bij Google of Microsoft. Hierdoor sturen facturen
            altijd vanaf jouw eigen mailadres en blijft de toegang volledig bij jou. Volg de stap-voor-stap handleiding hieronder per provider.
        </div>

        {{-- Provider cards --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- ============================== GOOGLE ============================== --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-8 h-8 flex-shrink-0" viewBox="0 0 48 48"><path fill="#fbc02d" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.6-6 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.9 1.1 8 3.1l5.7-5.7C34.3 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 20-8 20-20 0-1.3-.1-2.3-.4-3.5z"/><path fill="#e53935" d="M6.3 14.7l6.6 4.8C14.6 16 18.9 13 24 13c3 0 5.9 1.1 8 3.1l5.7-5.7C34.3 7.1 29.4 5 24 5 16.3 5 9.7 9 6.3 14.7z"/><path fill="#4caf50" d="M24 44c5.3 0 10.1-2 13.7-5.3l-6.3-5.2c-2 1.4-4.5 2.3-7.4 2.3-5.3 0-9.7-3.4-11.3-8l-6.5 5C9.5 40 16.2 44 24 44z"/><path fill="#1565c0" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.3-4 5.7l6.3 5.2C41.6 36 44 31 44 24c0-1.3-.1-2.3-.4-3.5z"/></svg>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Google Workspace</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Verstuur via Gmail API</p>
                            </div>
                        </div>
                        @if($googleConfigured)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Credentials ingesteld
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                Nog instellen
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    @if($googleConfigured)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('mail-connections.redirect', 'google') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-lg">
                                Koppel Google account →
                            </a>
                            <form method="POST" action="{{ route('mail-connections.credentials.delete', 'google') }}"
                                  onsubmit="return confirm('Google OAuth-credentials verwijderen? Bestaande gekoppelde accounts werken dan niet meer.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg dark:bg-red-900/20 dark:text-red-400">
                                    Credentials verwijderen
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- Toggle handleiding/setup --}}
                    <button type="button" @click="showGoogleSetup = !showGoogleSetup"
                            class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                        <svg class="w-4 h-4 transition-transform" :class="showGoogleSetup ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span x-text="showGoogleSetup ? 'Verberg handleiding' : ($googleConfigured ? 'Credentials wijzigen / handleiding tonen' : 'Toon stap-voor-stap handleiding')"></span>
                    </button>

                    <div x-show="showGoogleSetup" x-cloak x-transition.opacity class="space-y-4">

                        {{-- Redirect URI --}}
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Redirect URI (kopieer deze)</label>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 p-2 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded font-mono break-all">{{ $googleRedirectUri }}</code>
                                <button type="button" @click="copy('{{ $googleRedirectUri }}', 'google-uri')"
                                        class="px-3 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded">
                                    <span x-show="copied !== 'google-uri'">Kopieer</span>
                                    <span x-show="copied === 'google-uri'">Gekopieerd!</span>
                                </button>
                            </div>
                        </div>

                        {{-- Stappenplan --}}
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">Stappenplan</p>
                            <ol class="text-sm text-blue-900 dark:text-blue-200 list-decimal list-inside space-y-2">
                                <li>Open de <a href="https://console.cloud.google.com/" target="_blank" rel="noopener" class="underline">Google Cloud Console</a> en log in met je Google account.</li>
                                <li>Maak een nieuw project aan (of selecteer een bestaand project).</li>
                                <li>Ga naar <em>APIs &amp; Services → Library</em> en zoek &quot;Gmail API&quot;. Klik op <em>Enable</em>.</li>
                                <li>Ga naar <em>APIs &amp; Services → OAuth consent screen</em>. Kies <em>External</em> (tenzij je Workspace gebruikt), vul de basisgegevens in en voeg je eigen e-mailadres toe als test-user.</li>
                                <li>Ga naar <em>APIs &amp; Services → Credentials → Create Credentials → OAuth client ID</em>.</li>
                                <li>Kies <em>Web application</em> als type. Geef het een naam (bv. &quot;Hallo Invoicing&quot;).</li>
                                <li>Plak de Redirect URI hierboven in het veld <em>Authorized redirect URIs</em>.</li>
                                <li>Klik <em>Create</em>. Je krijgt nu een <strong>Client ID</strong> en <strong>Client Secret</strong>.</li>
                                <li>Plak die hieronder en klik <em>Opslaan</em>.</li>
                            </ol>
                        </div>

                        {{-- Form --}}
                        <form method="POST" action="{{ route('mail-connections.credentials.save', 'google') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Client ID</label>
                                <input type="text" name="client_id" required
                                       value="{{ old('client_id', $user->google_client_id) }}"
                                       placeholder="123456789-abc...apps.googleusercontent.com"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Client Secret</label>
                                <div class="relative">
                                    <input :type="showGoogleSecret ? 'text' : 'password'" name="client_secret" required
                                           value="{{ old('client_secret', $user->google_client_secret) }}"
                                           placeholder="GOCSPX-xxxxxxxxxxxxxxxxxxxx"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-20 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <button type="button" @click="showGoogleSecret = !showGoogleSecret"
                                            class="absolute inset-y-0 right-0 px-3 text-xs text-gray-600 dark:text-gray-300">
                                        <span x-text="showGoogleSecret ? 'Verberg' : 'Toon'"></span>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Wordt encrypted opgeslagen.</p>
                            </div>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                Opslaan
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ============================== MICROSOFT ============================== --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-5 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <svg class="w-8 h-8 flex-shrink-0" viewBox="0 0 23 23"><path fill="#f35325" d="M1 1h10v10H1z"/><path fill="#81bc06" d="M12 1h10v10H12z"/><path fill="#05a6f0" d="M1 12h10v10H1z"/><path fill="#ffba08" d="M12 12h10v10H12z"/></svg>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Microsoft 365</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Verstuur via Microsoft Graph</p>
                            </div>
                        </div>
                        @if($microsoftConfigured)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Credentials ingesteld
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                Nog instellen
                            </span>
                        @endif
                    </div>
                </div>

                <div class="p-5 space-y-4">
                    @if($microsoftConfigured)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('mail-connections.redirect', 'microsoft') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-700 rounded-lg">
                                Koppel Microsoft 365 account →
                            </a>
                            <form method="POST" action="{{ route('mail-connections.credentials.delete', 'microsoft') }}"
                                  onsubmit="return confirm('Microsoft OAuth-credentials verwijderen? Bestaande gekoppelde accounts werken dan niet meer.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="px-3 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg dark:bg-red-900/20 dark:text-red-400">
                                    Credentials verwijderen
                                </button>
                            </form>
                        </div>
                    @endif

                    <button type="button" @click="showMicrosoftSetup = !showMicrosoftSetup"
                            class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                        <svg class="w-4 h-4 transition-transform" :class="showMicrosoftSetup ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span x-text="showMicrosoftSetup ? 'Verberg handleiding' : ($microsoftConfigured ? 'Credentials wijzigen / handleiding tonen' : 'Toon stap-voor-stap handleiding')"></span>
                    </button>

                    <div x-show="showMicrosoftSetup" x-cloak x-transition.opacity class="space-y-4">

                        <div class="p-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase mb-1">Redirect URI (kopieer deze)</label>
                            <div class="flex items-center gap-2">
                                <code class="flex-1 p-2 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded font-mono break-all">{{ $microsoftRedirectUri }}</code>
                                <button type="button" @click="copy('{{ $microsoftRedirectUri }}', 'ms-uri')"
                                        class="px-3 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded">
                                    <span x-show="copied !== 'ms-uri'">Kopieer</span>
                                    <span x-show="copied === 'ms-uri'">Gekopieerd!</span>
                                </button>
                            </div>
                        </div>

                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">Stappenplan</p>
                            <ol class="text-sm text-blue-900 dark:text-blue-200 list-decimal list-inside space-y-2">
                                <li>Open het <a href="https://portal.azure.com/" target="_blank" rel="noopener" class="underline">Azure Portal</a> en log in met je Microsoft 365 account.</li>
                                <li>Ga naar <em>Microsoft Entra ID → App registrations → New registration</em>.</li>
                                <li>Geef de app een naam (bv. &quot;Hallo Invoicing&quot;).</li>
                                <li>Bij <em>Supported account types</em>: kies meestal &quot;Accounts in any organizational directory and personal Microsoft accounts&quot;.</li>
                                <li>Plak de Redirect URI hierboven (selecteer <em>Web</em> als platform). Klik <em>Register</em>.</li>
                                <li>Kopieer de <strong>Application (client) ID</strong> van de overzichtspagina.</li>
                                <li>Optioneel: kopieer ook de <strong>Directory (tenant) ID</strong> als je single-tenant wilt; anders gebruik je &quot;common&quot;.</li>
                                <li>Ga naar <em>Certificates &amp; secrets → New client secret</em>. Kopieer de <strong>Value</strong> (let op: niet de Secret ID, en hij is maar één keer zichtbaar).</li>
                                <li>Ga naar <em>API permissions → Add permission → Microsoft Graph → Delegated</em> en voeg toe: <code>Mail.Send</code>, <code>User.Read</code>, <code>offline_access</code>.</li>
                                <li>Plak Client ID + Secret hieronder en klik <em>Opslaan</em>.</li>
                            </ol>
                        </div>

                        <form method="POST" action="{{ route('mail-connections.credentials.save', 'microsoft') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Application (client) ID</label>
                                <input type="text" name="client_id" required
                                       value="{{ old('client_id', $user->microsoft_client_id) }}"
                                       placeholder="00000000-0000-0000-0000-000000000000"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Client Secret <span class="text-gray-500 font-normal">(de &quot;Value&quot; uit Azure)</span></label>
                                <div class="relative">
                                    <input :type="showMicrosoftSecret ? 'text' : 'password'" name="client_secret" required
                                           value="{{ old('client_secret', $user->microsoft_client_secret) }}"
                                           placeholder="abc~XYZ.0123456789..."
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-20 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <button type="button" @click="showMicrosoftSecret = !showMicrosoftSecret"
                                            class="absolute inset-y-0 right-0 px-3 text-xs text-gray-600 dark:text-gray-300">
                                        <span x-text="showMicrosoftSecret ? 'Verberg' : 'Toon'"></span>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Wordt encrypted opgeslagen.</p>
                            </div>
                            <div>
                                <label class="block mb-1 text-sm font-medium text-gray-900 dark:text-white">Tenant ID <span class="text-gray-500 font-normal">(optioneel — laat 'common' staan voor multi-tenant)</span></label>
                                <input type="text" name="tenant_id"
                                       value="{{ old('tenant_id', $user->microsoft_tenant_id ?: 'common') }}"
                                       placeholder="common"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                Opslaan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
