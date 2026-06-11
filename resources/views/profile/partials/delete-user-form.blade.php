<section class="space-y-5">
    <div>
        <h2 class="text-xl font-black text-[#F8FAFC]">Delete Account</h2>
        <p class="mt-1 text-sm text-[#94A3B8]">Once your account is deleted, all resources and data will be permanently removed. This action cannot be undone.</p>
    </div>

    <button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="rounded-xl bg-[#EF4444] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-400"
    >Delete Account</button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="rounded-2xl bg-[#0B1220] p-6">
            @csrf
            @method('delete')

            <h2 class="text-xl font-black text-[#F8FAFC]">Are you sure you want to delete your account?</h2>
            <p class="mt-2 text-sm text-[#94A3B8]">This will permanently delete all your bots, commands, settings, and account data. Please enter your password to confirm.</p>

            <div class="mt-6">
                <x-input-label for="delete_password" value="Password" class="sr-only" />
                <x-text-input
                    id="delete_password" name="password" type="password"
                    class="mt-1 block w-full"
                    placeholder="Enter your password to confirm"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 text-[#FCA5A5]" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="$dispatch('close')" class="rounded-xl border border-[#1E293B] bg-[#0B1220] px-5 py-2.5 text-sm font-bold text-[#F8FAFC] transition hover:border-[#229ED9]">Cancel</button>
                <button type="submit" class="rounded-xl bg-[#EF4444] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-400">Delete Account</button>
            </div>
        </form>
    </x-modal>
</section>
