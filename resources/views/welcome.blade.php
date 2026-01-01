<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ReaderNews') }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|roboto:400,500,700" rel="stylesheet" />
        
        <!-- Icons -->
        <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <script>
            // Check theme on load
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>
    </head>
    <body class="antialiased bg-surface-50 dark:bg-surface-950 text-surface-900 dark:text-gray-100 min-h-screen flex flex-col font-sans transition-colors duration-300">
        
        <!-- Header -->
        <header class="px-6 py-4 flex items-center justify-between max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                <ion-icon name="newspaper-outline" class="text-3xl"></ion-icon>
                <span class="text-2xl font-bold tracking-tight">ReaderNews</span>
            </div>
            
            <nav class="flex items-center gap-4">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="font-medium hover:text-primary-600 transition-colors">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium hover:text-primary-600 transition-colors">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm shadow-primary-500/30">Get Started</a>
                        @endif
                    @endauth
                @endif
                
                <!-- Theme Toggle -->
                <button 
                    onclick="document.documentElement.classList.toggle('dark'); localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'"
                    class="p-2 rounded-full hover:bg-surface-200 dark:hover:bg-surface-800 transition-colors text-surface-500"
                >
                    <ion-icon name="moon-outline" class="hidden dark:block"></ion-icon>
                    <ion-icon name="sunny-outline" class="block dark:hidden"></ion-icon>
                </button>
            </nav>
        </header>

        <!-- Hero Section -->
        <main class="flex-1 flex items-center justify-center px-6 py-12 lg:py-20">
            <div class="max-w-7xl w-full grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                <div class="space-y-8">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 text-sm font-medium border border-primary-100 dark:border-primary-800">
                        <span class="relative flex h-2 w-2">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                        </span>
                        v1.0 Now Available with AI Features
                    </div>
                    
                    <h1 class="text-5xl lg:text-7xl font-bold tracking-tight leading-tight">
                        Your internet, <br>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-indigo-600 dark:from-primary-400 dark:to-indigo-400">decluttered.</span>
                    </h1>
                    
                    <p class="text-xl text-surface-600 dark:text-surface-400 leading-relaxed max-w-lg">
                        Follow your favorite sites, organize with tags, and read in a distraction-free environment. Now with local AI integration.
                    </p>
                    
                    <div class="flex items-center gap-4 pt-4">
                        <a href="{{ route('register') }}" class="bg-surface-900 dark:bg-white text-white dark:text-surface-900 px-8 py-3.5 rounded-xl font-bold text-lg hover:-translate-y-1 hover:shadow-lg transition-all">Start Reading Free</a>
                        <a href="#features" class="px-8 py-3.5 rounded-xl font-medium text-surface-600 dark:text-surface-300 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors">Learn more</a>
                    </div>
                    
                    <div class="flex items-center gap-8 pt-8 text-surface-400 dark:text-surface-500">
                        <div class="flex items-center gap-2">
                            <ion-icon name="logo-rss" class="text-xl"></ion-icon> RSS/Atom
                        </div>
                        <div class="flex items-center gap-2">
                            <ion-icon name="globe-outline" class="text-xl"></ion-icon> Web Scraping
                        </div>
                        <div class="flex items-center gap-2">
                            <ion-icon name="hardware-chip-outline" class="text-xl"></ion-icon> Local AI
                        </div>
                    </div>
                </div>
                
                <!-- Hero Visual -->
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-tr from-primary-500/20 to-purple-500/20 rounded-3xl blur-3xl transform -rotate-6"></div>
                    <div class="relative bg-white dark:bg-surface-900 rounded-2xl shadow-2xl border border-surface-200 dark:border-surface-800 overflow-hidden transform rotate-2 hover:rotate-0 transition-transform duration-500">
                        <!-- Tiny Mockup Interface -->
                        <div class="h-8 bg-surface-100 dark:bg-surface-800 border-b border-surface-200 dark:border-surface-700 flex items-center px-4 gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="p-6 space-y-4 opacity-50 dark:opacity-80 grayscale hover:grayscale-0 transition-all duration-500">
                            <div class="flex gap-4">
                                <div class="w-1/3 h-32 bg-surface-100 dark:bg-surface-800 rounded-lg animate-pulse"></div>
                                <div class="w-2/3 space-y-3">
                                    <div class="h-6 w-3/4 bg-surface-200 dark:bg-surface-700 rounded animate-pulse"></div>
                                    <div class="h-4 w-full bg-surface-100 dark:bg-surface-800 rounded animate-pulse"></div>
                                    <div class="h-4 w-5/6 bg-surface-100 dark:bg-surface-800 rounded animate-pulse"></div>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="w-1/3 h-32 bg-surface-100 dark:bg-surface-800 rounded-lg animate-pulse"></div>
                                <div class="w-2/3 space-y-3">
                                    <div class="h-6 w-1/2 bg-surface-200 dark:bg-surface-700 rounded animate-pulse"></div>
                                    <div class="h-4 w-full bg-surface-100 dark:bg-surface-800 rounded animate-pulse"></div>
                                    <div class="h-4 w-4/6 bg-surface-100 dark:bg-surface-800 rounded animate-pulse"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Floating Badge -->
                        <div class="absolute bottom-6 right-6 bg-primary-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                                <ion-icon name="checkmark-outline" class="text-xl"></ion-icon>
                            </div>
                            <div>
                                <div class="text-xs opacity-80">Sync Complete</div>
                                <div class="font-bold">12 New Articles</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="py-6 text-center text-sm text-surface-400 dark:text-surface-600">
            &copy; {{ date('Y') }} ReaderNews. Built with Laravel.
        </footer>
    </body>
</html>
