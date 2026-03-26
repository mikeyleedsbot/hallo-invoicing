<x-app-layout>
    <div class="space-y-6" x-data="{ showModal: false, editMode: false, current: null,
        openCreate() { this.editMode = false; this.current = null; this.showModal = true; },
        openEdit(item) { this.editMode = true; this.current = item; this.showModal = true; }
    }">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">BTW Tarieven</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Beheer de BTW-tarieven die beschikbaar zijn op facturen en offertes</p>
            </div>
            <button @click="openCreate()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors focus:ring-4 focus:ring-blue-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nieuw Tarief
            </button>
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

        <!-- Tabel -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4">Naam</th>
                            <th scope="col" class="px-6 py-4">Percentage</th>
                            <th scope="col" class="px-6 py-4">Standaard</th>
                            <th scope="col" class="px-6 py-4"><span class="sr-only">Acties</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vatRates as $vat)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $vat->name }}
                            </th>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ number_format($vat->rate, 0) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($vat->is_default)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Standaard
                                    </span>
                                @else
                                    <form method="POST" action="{{ route('vat-rates.set-default', $vat) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-gray-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400 transition-colors">
                                            Instellen als standaard
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openEdit(@json($vat))"
                                        class="font-medium text-blue-600 dark:text-blue-500 hover:underline mr-3">Bewerken</button>
                                @if(!$vat->is_default)
                                <form method="POST" action="{{ route('vat-rates.destroy', $vat) }}" class="inline"
                                      onsubmit="return confirm('BTW tarief {{ $vat->name }} verwijderen?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="font-medium text-red-600 dark:text-red-500 hover:underline">Verwijderen</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">Geen BTW tarieven gevonden</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info box -->
        <div class="p-4 bg-blue-50 dark:bg-gray-700 border border-blue-200 dark:border-gray-600 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-white">
                <strong>Tip:</strong> Het standaard tarief wordt automatisch ingevuld bij het aanmaken van nieuwe factuurregels. Je kunt per factuurregel altijd een ander tarief kiezen.
            </p>
        </div>

        <!-- Modal -->
        <div x-show="showModal"
             @click.away="showModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">

            <div class="fixed inset-0 bg-black bg-opacity-50" style="backdrop-filter: blur(4px);"></div>

            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.stop
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-md bg-white rounded-xl shadow-2xl dark:bg-gray-800">

                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white"
                            x-text="editMode ? 'BTW Tarief Bewerken' : 'Nieuw BTW Tarief'"></h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form :action="editMode ? '/btw-tarieven/' + current.id : '{{ route('vat-rates.store') }}'" method="POST">
                        @csrf
                        <template x-if="editMode">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="p-6 space-y-5">
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Naam *</label>
                                <input type="text" name="name" :value="current?.name" required
                                       placeholder="bijv. BTW Hoog"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Percentage *</label>
                                <div class="relative">
                                    <input type="number" name="rate" :value="current?.rate" required
                                           min="0" max="100" step="0.01"
                                           placeholder="21"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-10 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 font-medium">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="showModal = false"
                                    class="px-5 py-2.5 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600">
                                Annuleren
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                                <span x-text="editMode ? 'Bijwerken' : 'Aanmaken'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
