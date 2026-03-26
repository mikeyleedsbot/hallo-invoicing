<x-app-layout>
    <div class="space-y-6"
         x-data="{
             showModal: false,
             editMode: false,
             currentUser: null,
             activeDropdown: null,
             openCreateModal() { this.editMode = false; this.currentUser = null; this.showModal = true; },
             openEditModal(user) { this.editMode = true; this.currentUser = user; this.showModal = true; },
             toggleDropdown(id) { this.activeDropdown = this.activeDropdown === id ? null : id; },
             closeDropdowns() { this.activeDropdown = null; }
         }"

         @click="closeDropdowns()">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Gebruikersbeheer</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Beheer accounts die toegang hebben tot de invoicing tool</p>
            </div>
            <button @click="openCreateModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors focus:ring-4 focus:ring-blue-300 dark:focus:ring-blue-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nieuwe Gebruiker
            </button>
        </div>

        @if(session('success'))
        <div class="flex items-center p-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800">
            <svg class="flex-shrink-0 inline w-4 h-4 me-3" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="flex items-center p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700" style="overflow:visible;">
            <div>
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400" style="overflow:visible;">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4">Naam</th>
                            <th scope="col" class="px-6 py-4">E-mail</th>
                            <th scope="col" class="px-6 py-4">Rol</th>
                            <th scope="col" class="px-6 py-4">MFA</th>
                            <th scope="col" class="px-6 py-4">Aangemaakt</th>
                            <th scope="col" class="px-6 py-4"><span class="sr-only">Acties</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $u)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                {{ $u->name }}
                                @if($u->id === Auth::id())
                                    <span class="ml-1 text-xs text-gray-400 font-normal">(jij)</span>
                                @endif
                                @if($u->company_name)
                                    <p class="text-xs text-gray-400 font-normal mt-0.5">{{ $u->company_name }}</p>
                                @endif
                            </th>
                            <td class="px-6 py-4">{{ $u->email }}</td>
                            <td class="px-6 py-4">
                                @if($u->is_admin)
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Admin</span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">Gebruiker</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($u->invite_token)
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Uitgenodigd</span>
                                @elseif($u->mfa_enabled)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        MFA actief
                                    </span>
                                @else
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Geen MFA</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">{{ $u->created_at->format('d-m-Y') }}</td>
                            <td class="px-6 py-4 text-right" style="position:relative;overflow:visible;" id="td-{{ $u->id }}">
                                <!-- Acties dropdown -->
                                <div class="relative inline-block text-left">
                                    <button onclick="toggleMenu({{ $u->id }}, event)" type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                        Acties
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                    <div id="menu-{{ $u->id }}"
                                         class="hidden absolute right-0 mt-1 w-48 bg-white dark:bg-gray-700 rounded-lg shadow-xl border border-gray-200 dark:border-gray-600"
                                         style="z-index:9999;">
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button type="button"
                                                        onclick="closeAllMenus(); editUser({{ $u->id }})"
                                                        class="flex items-center gap-2 w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z"/></svg>
                                                    Bewerken
                                                </button>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('users.resend-invite', $u) }}"
                                                      onsubmit="return confirm('Uitnodiging opnieuw sturen naar {{ $u->email }}?')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="flex items-center gap-2 w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-blue-600 dark:text-blue-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                        Uitnodiging sturen
                                                    </button>
                                                </form>
                                            </li>
                                            @if($u->mfa_enabled)
                                            <li>
                                                <form method="POST" action="{{ route('users.reset-mfa', $u) }}"
                                                      onsubmit="return confirm('MFA resetten voor {{ $u->name }}?')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="flex items-center gap-2 w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-amber-600 dark:text-amber-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                                        Reset MFA
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                            @if($u->id !== Auth::id())
                                            <li class="border-t border-gray-100 dark:border-gray-600">
                                                <form method="POST" action="{{ route('users.destroy', $u) }}"
                                                      onsubmit="return confirm('Gebruiker {{ $u->name }} verwijderen?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                            class="flex items-center gap-2 w-full px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 text-red-600 dark:text-red-400">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                        Verwijderen
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                                {{-- User data voor JS --}}
                                <script>
                                    window.__users = window.__users || {};
                                    window.__users[{{ $u->id }}] = @json($u);
                                </script>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-700 mb-4">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Nog geen gebruikers</h3>
                                    <p class="text-gray-600 dark:text-gray-400 mb-6">Maak een eerste gebruiker aan.</p>
                                    <button @click="openCreateModal()"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Nieuwe Gebruiker
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
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

            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50" style="backdrop-filter: blur(4px);"></div>

            <!-- Modal -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click.stop
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-2xl bg-white rounded-xl shadow-2xl dark:bg-gray-800">

                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white"
                            x-text="editMode ? 'Gebruiker Bewerken' : 'Nieuwe Gebruiker'"></h3>
                        <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Form -->
                    <form :action="editMode ? '/gebruikers/' + currentUser.id : '{{ route('users.store') }}'" method="POST">
                        @csrf
                        <template x-if="editMode">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Naam *</label>
                                    <input type="text" name="name" :value="currentUser?.name" required
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Bedrijfsnaam</label>
                                    <input type="text" name="company_name" :value="currentUser?.company_name"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">E-mailadres *</label>
                                    <input type="email" name="email" :value="currentUser?.email" required
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Telefoonnummer</label>
                                    <input type="text" name="phone" :value="currentUser?.phone"
                                           class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Adres</label>
                                <input type="text" name="address" :value="currentUser?.address"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Plaats</label>
                                <input type="text" name="city" :value="currentUser?.city"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg"
                                 x-show="!currentUser || currentUser.id !== {{ Auth::id() }}">
                                <input type="checkbox" name="is_admin" id="modal_is_admin" value="1"
                                       :checked="currentUser?.is_admin"
                                       class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                                <label for="modal_is_admin" class="text-sm font-medium text-gray-900 dark:text-white">
                                    Hallo Admin
                                    <span class="font-normal text-gray-500 dark:text-gray-400"> — toegang tot gebruikersbeheer</span>
                                </label>
                            </div>
                            <!-- Uitleg bij aanmaken -->
                            <div x-show="!editMode" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-xs text-blue-800 dark:text-blue-300">
                                    📧 De gebruiker ontvangt een uitnodigingsmail om zelf een wachtwoord en MFA in te stellen.
                                </p>
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
<script>
function closeAllMenus() {
    document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add('hidden'));
}

function toggleMenu(id, event) {
    event.stopPropagation();
    const menu = document.getElementById('menu-' + id);
    const isHidden = menu.classList.contains('hidden');
    closeAllMenus();
    if (isHidden) menu.classList.remove('hidden');
}

function editUser(id) {
    const user = window.__users[id];
    if (!user) return;
    const el = document.querySelector('.space-y-6');
    if (el) Alpine.$data(el).openEditModal(user);
}

document.addEventListener('click', closeAllMenus);
</script>
</x-app-layout>
