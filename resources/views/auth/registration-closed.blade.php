<x-guest-layout>
    <div class="space-y-5 text-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Registration Closed</h1>
            <p class="mt-2 text-sm text-gray-600">
                New account registration is currently disabled. Existing users can still log in.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Login
            </a>
            <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                Back Home
            </a>
        </div>
    </div>
</x-guest-layout>
