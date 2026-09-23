<x-app-layout>
@section('title', 'Bank')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bankafschriften</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Importeer een afschrift en koppel de bijschrijvingen aan je verkoopfacturen.
            </p>
        </div>

        @if(session('success'))
            <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400">{{ session('info') }}</div>
        @endif
        @if($errors->any())
            <div class="p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
                <p class="font-medium mb-1">Importeren is niet gelukt:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Uploaden --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Afschrift importeren</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                CSV (puntkomma of komma), CAMT.053-XML of MT940. Een zip met één bestand mag ook.
                Het formaat wordt automatisch herkend.
            </p>

            <form action="{{ route('bank.store') }}" method="POST" enctype="multipart/form-data"
                  x-data="statementUpload()" @submit="start($event)"
                  class="flex flex-wrap items-end gap-3">
                @csrf
                <div class="flex-1 min-w-[16rem]">
                    <label for="statement" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bestand</label>
                    <input type="file" name="statement" id="statement" required
                           accept=".csv,.txt,.xml,.sta,.940,.zip" :disabled="busy"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:text-white disabled:opacity-60">
                </div>
                <button type="submit" :disabled="busy"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg inline-flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed">
                    <svg x-show="busy" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="busy ? (uploading ? 'Uploaden…' : 'Verwerken…') : 'Importeren'">Importeren</span>
                </button>

                {{-- Voortgang van het uploaden. Dit is de werkelijke voortgang die
                     de browser meldt, geen geschatte animatie. --}}
                <div x-show="busy" x-cloak class="w-full">
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                        <span x-text="uploading ? 'Bestand uploaden' : 'Transacties inlezen en matchen'"></span>
                        <span x-show="uploading" x-text="percent + '%'"></span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden dark:bg-gray-700">
                        <div class="h-2 bg-blue-600 transition-all duration-150"
                             :class="uploading ? '' : 'animate-pulse'"
                             :style="uploading ? ('width: ' + percent + '%') : 'width: 100%'"></div>
                    </div>
                    <p x-show="!uploading" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Het bestand staat er; even geduld terwijl de transacties worden ingelezen.
                    </p>
                </div>
            </form>
        </div>

        {{-- Eerdere imports --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white p-6 pb-3">Eerdere imports</h2>

            @if($sessions->isEmpty())
                <p class="px-6 pb-6 text-sm text-gray-500 dark:text-gray-400">Er is nog niets geïmporteerd.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Bestand</th>
                                <th class="px-6 py-3">Formaat</th>
                                <th class="px-6 py-3">Periode</th>
                                <th class="px-6 py-3">Bewaard</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sessions as $s)
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $s->original_filename }}</td>
                                <td class="px-6 py-4">{{ $s->formatLabel() }}</td>
                                <td class="px-6 py-4">
                                    @if($s->period_from){{ $s->period_from->format('d-m-Y') }} t/m {{ $s->period_to?->format('d-m-Y') }}@else&mdash;@endif
                                </td>
                                <td class="px-6 py-4">{{ $s->transactions_count }}</td>
                                <td class="px-6 py-4">
                                    @if($s->isOpen())
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Nog matchen</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Afgerond</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        @if($s->isOpen())
                                            <a href="{{ route('bank.show', $s) }}" class="px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Matchen</a>
                                        @endif
                                        <form action="{{ route('bank.destroy', $s) }}" method="POST"
                                              onsubmit="return confirm('Deze import weggooien? Ook de koppelingen die eruit voortkwamen verdwijnen en die facturen komen weer op openstaand.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20 rounded-lg">Weggooien</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
            Bij het afronden van een import worden alle transacties die aan geen enkele factuur gekoppeld zijn
            verwijderd. Er blijven dus geen bankgegevens bewaard die nergens voor nodig zijn.
        </p>
    </div>

    <script>
        function statementUpload() {
            return {
                busy: false,
                uploading: false,
                percent: 0,

                start(event) {
                    const form = event.target;

                    // Zonder XMLHttpRequest gewoon op de normale manier versturen
                    if (!window.XMLHttpRequest || !form.querySelector('input[type=file]').files.length) {
                        this.busy = true;
                        return;
                    }

                    event.preventDefault();
                    this.busy = true;
                    this.uploading = true;
                    this.percent = 0;

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                    // Echte voortgang, gemeld door de browser
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            this.percent = Math.round((e.loaded / e.total) * 100);
                        }
                    });

                    // Upload klaar: vanaf hier is de server aan het werk
                    xhr.upload.addEventListener('load', () => {
                        this.uploading = false;
                        this.percent = 100;
                    });

                    xhr.addEventListener('load', () => {
                        window.location = xhr.responseURL || form.action;
                    });

                    xhr.addEventListener('error', () => {
                        // Bij een netwerkfout terugvallen op een gewone verzending
                        this.busy = false;
                        this.uploading = false;
                        form.submit();
                    });

                    xhr.send(new FormData(form));
                },
            };
        }
    </script>
</x-app-layout>
