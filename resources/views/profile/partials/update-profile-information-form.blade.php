<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Profielgegevens</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Beheer je accountgegevens en contactinformatie.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <!-- Naam & Bedrijfsnaam -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="name" value="Naam *" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                    :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>
            <div>
                <x-input-label for="company_name" value="Bedrijfsnaam" />
                <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full"
                    :value="old('company_name', $user->company_name)" autocomplete="organization" />
                <x-input-error class="mt-2" :messages="$errors->get('company_name')" />
            </div>
        </div>

        <!-- E-mail & Telefoon -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="email" value="E-mailadres *" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                    :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
            <div>
                <x-input-label for="phone" value="Telefoonnummer" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                    :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
        </div>

        <!-- Adres & Plaats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="address" value="Adres" />
                <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                    :value="old('address', $user->address)" autocomplete="street-address" />
                <x-input-error class="mt-2" :messages="$errors->get('address')" />
            </div>
            <div>
                <x-input-label for="city" value="Plaats" />
                <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                    :value="old('city', $user->city)" autocomplete="address-level2" />
                <x-input-error class="mt-2" :messages="$errors->get('city')" />
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>Opslaan</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-gray-600 dark:text-gray-400">Opgeslagen.</p>
            @endif
        </div>
    </form>
</section>
