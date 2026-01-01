<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Email Address</label>
            <input id="email" class="w-full px-4 py-2 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all" 
                   type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-surface-700 dark:text-surface-300 mb-1">Password</label>
            <input id="password" class="w-full px-4 py-2 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                   type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded border-surface-300 text-primary-600 shadow-sm focus:ring-primary-500 bg-surface-100 dark:bg-surface-800 dark:border-surface-700" name="remember">
                <span class="ml-2 text-sm text-surface-600 dark:text-surface-400">Remember me</span>
            </label>
            
            @if (Route::has('password.request'))
                <a class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 font-medium transition-colors" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg font-bold transition-colors shadow-lg shadow-primary-600/20">
                Log in
            </button>
            <div class="mt-4 text-center text-sm text-surface-500">
                Don't have an account? <a href="{{ route('register') }}" class="text-primary-600 font-medium hover:underline">Sign up</a>
            </div>
        </div>
    </form>
</x-guest-layout>
