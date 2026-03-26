<x-app-layout>
    <div class="space-y-6" x-data="{ showTest: false }">

        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">E-mailinstellingen</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Configureer de Azure Mailer API voor het versturen van uitnodigingen en notificaties</p>
        </div>

        @if(session('success'))
        <div class="flex items-center p-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/></svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('warning'))
        <div class="flex items-center p-4 text-sm text-amber-800 border border-amber-300 rounded-lg bg-amber-50 dark:bg-gray-800 dark:text-amber-400 dark:border-amber-800">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/></svg>
            <span class="font-medium">{{ session('warning') }}</span>
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
                @if($settings->isConfigured())
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">E-mail geconfigureerd</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Uitnodigingsmails kunnen worden verstuurd</p>
                </div>
                @else
                <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 100 18A9 9 0 0012 3z"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">E-mail niet geconfigureerd</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Vul de API-gegevens in om uitnodigingen te kunnen versturen</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Instellingen formulier -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">API Configuratie</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Verbinding met de Azure Mailer API</p>
            </div>
            <form action="{{ route('email-settings.update') }}" method="POST" class="p-6 space-y-5">
                @csrf @method('PUT')

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">API URL *</label>
                    <input type="url" name="api_url" value="{{ old('api_url', $settings->api_url) }}" required
                           placeholder="https://jouw-mailer.azurewebsites.net/api/sendEmail"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">De basis-URL zonder <code>?code=</code> parameter</p>
                </div>

                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                        API Key / Code
                        @if($settings->isConfigured())
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Opgeslagen ✓</span>
                        @endif
                    </label>
                    <input type="password" name="api_key"
                           placeholder="{{ $settings->isConfigured() ? 'Laat leeg om bestaande key te bewaren' : 'Voer API key in' }}"
                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white font-mono">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Wordt versleuteld opgeslagen. Laat leeg om de huidige key te bewaren.</p>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Afzendernaam</label>
                        <input type="text" name="from_name" value="{{ old('from_name', $settings->from_name) }}" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Afzender e-mail</label>
                        <input type="email" name="from_email" value="{{ old('from_email', $settings->from_email) }}" required
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>
                </div>

                <!-- Technische info -->
                <div class="py-4">
                    <div class="p-4 bg-gray-100 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-3">📡 Hoe werkt het?</p>
                        <p class="font-mono text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md px-3 py-2 mb-3">POST {api_url}?code={api_key}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-300 mb-2">Met header <code class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 px-1.5 py-0.5 rounded text-gray-700 dark:text-gray-200">Content-Type: application/json</code> en body:</p>
                        <pre class="text-xs text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md px-3 py-2 overflow-x-auto">{"to": "...", "subject": "...", "html": "..."}</pre>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-8 py-2.5">
                        Opslaan
                    </button>
                </div>
            </form>

        </div>

        <!-- Testmail -->
        @if($settings->isConfigured())
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
        @endif

    </div>
</x-app-layout>
