<x-layout title="Profile Settings">
    <div class="max-w-4xl mx-auto py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold font-serif text-surface-900 dark:text-white mb-2">My Profile</h1>
            <p class="text-surface-500 font-medium">Manage your account settings and preferences.</p>
        </div>

        <div class="space-y-8">
            <!-- Profile Information -->
            <div class="p-6 sm:p-8 bg-white dark:bg-surface-900 shadow-sm border border-surface-200 dark:border-surface-800 rounded-xl transition-all hover:shadow-md">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <!-- Update Password -->
            <div class="p-6 sm:p-8 bg-white dark:bg-surface-900 shadow-sm border border-surface-200 dark:border-surface-800 rounded-xl transition-all hover:shadow-md">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Delete Account -->
            <div class="p-6 sm:p-8 bg-white dark:bg-surface-900 shadow-sm border border-surface-200 dark:border-surface-800 rounded-xl transition-all hover:shadow-md opacity-80 hover:opacity-100 hover:border-red-200 dark:hover:border-red-900/30">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-layout>
