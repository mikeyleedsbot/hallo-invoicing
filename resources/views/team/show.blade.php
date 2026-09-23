<x-app-layout>
@section('title', 'Teamleden')
    <div class="space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Teamleden</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Iedereen in {{ $team->name }} werkt met dezelfde klanten, facturen en instellingen — elk lid heeft wel een eigen login en eigen tweestapsverificatie.
            </p>
        </div>

        @if(session('success'))
        <div class="flex items-center p-4 text-sm text-green-800 border border-green-300 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 dark:border-green-800">
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        @endif

        @if(session('warning'))
        <div class="flex items-center p-4 text-sm text-amber-800 border border-amber-300 rounded-lg bg-amber-50 dark:bg-gray-800 dark:text-amber-400 dark:border-amber-800">
            <span class="font-medium">{{ session('warning') }}</span>
        </div>
        @endif

        @if($errors->any())
        <div class="flex items-center p-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        {{-- Teamleden --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Leden</h2>
            </div>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Naam</th>
                        <th class="px-6 py-3">E-mail</th>
                        <th class="px-6 py-3">Rol</th>
                        @if(auth()->user()->isOwnerOfCurrentTeam())
                        <th class="px-6 py-3 text-right">Acties</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $member->name }}</td>
                        <td class="px-6 py-4">{{ $member->email }}</td>
                        <td class="px-6 py-4">
                            @if($member->pivot->role === \App\Models\Team::ROLE_OWNER)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Eigenaar</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Lid</span>
                            @endif
                        </td>
                        @if(auth()->user()->isOwnerOfCurrentTeam())
                        <td class="px-6 py-4 text-right">
                            @if($member->id !== $team->owner_id)
                            <form method="POST" action="{{ route('team.remove-member', $member) }}"
                                  onsubmit="return confirm('{{ $member->name }} uit het team verwijderen?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-500">Verwijderen</button>
                            </form>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(auth()->user()->isOwnerOfCurrentTeam())
        {{-- Openstaande uitnodigingen --}}
        @if($invitations->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Aanvragen en uitnodigingen</h2>
            </div>
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">E-mail</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Acties</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invitations as $invitation)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $invitation->email }}</td>
                        <td class="px-6 py-4">
                            @if($invitation->isApproved())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Uitgenodigd</span>
                            <span class="ms-1">{{ $invitation->approved_at->diffForHumans() }}</span>
                            @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">Wacht op goedkeuring</span>
                            <span class="ms-1">aangevraagd {{ $invitation->created_at->diffForHumans() }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <form method="POST" action="{{ route('team.cancel-invite', $invitation) }}"
                                  onsubmit="return confirm('{{ $invitation->isApproved() ? 'Uitnodiging' : 'Aanvraag' }} voor {{ $invitation->email }} intrekken?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-500">Intrekken</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Teamlid aanvragen --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Teamlid aanvragen</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Een extra teamlid brengt extra kosten met zich mee. Na je aanvraag regelen wij de facturatie; daarna ontvangt het nieuwe lid automatisch een uitnodiging.</p>
            </div>
            <form method="POST" action="{{ route('team.invite') }}" class="p-6 flex items-end gap-3"
                  onsubmit="return confirm('Teamlid ' + this.email.value + ' aanvragen? Een extra teamlid brengt extra licentiekosten met zich mee.')">
                @csrf
                <div class="flex-1">
                    <x-input-label for="email" value="E-mailadres" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" required />
                </div>
                <x-primary-button>Aanvragen</x-primary-button>
            </form>
        </div>
        @endif
    </div>
</x-app-layout>
