<section>
    <div class="mb-6">
        <h2 class="text-xl font-black text-[#F8FAFC]">Profile Information</h2>
        <p class="mt-1 text-sm text-[#94A3B8]">Update your account username, name, and email address.</p>
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2 text-[#FCA5A5]" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="username" :value="__('Username')" />
            <x-text-input id="username" name="username" type="text" class="mt-2 block w-full" :value="old('username', $user->username)" required autocomplete="username" />
            <x-input-error class="mt-2 text-[#FCA5A5]" :messages="$errors->get('username')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user->email)" required autocomplete="email" />
            <x-input-error class="mt-2 text-[#FCA5A5]" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>Save Changes</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-[#86EFAC]">Saved.</p>
            @endif
        </div>
    </form>
</section>
