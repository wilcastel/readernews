<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-surface-50 dark:bg-surface-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'ReaderNews' }}</title>

    <!-- Dark Mode Script (Prevents Flash) -->
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-surface-900 dark:text-surface-100 dark:bg-surface-950 flex overflow-hidden"
      x-data="{ 
        openAddModal: false,
        openEditModal: false,
        openVideoModal: false,
        sidebarOpen: false,
        sidebarMinimized: localStorage.getItem('sidebarMinimized') === 'true',
        editingFeed: { id: null, name: '', folder_id: '' },
        toggleSidebarMinimized() {
            this.sidebarMinimized = !this.sidebarMinimized;
            localStorage.setItem('sidebarMinimized', this.sidebarMinimized);
        }
      }"
      @edit-feed.window="
        editingFeed = $event.detail; 
        openEditModal = true;
      ">
    
    <!-- Edit Feed Modal -->
    <div x-show="openEditModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="openEditModal = false"></div>
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-xl w-full max-w-sm relative z-10 p-6 border border-surface-200 dark:border-surface-800">
            <h3 class="text-xl font-bold mb-4 dark:text-white">Edit Feed</h3>
            <form :action="'/feeds/' + editingFeed.id" method="POST">
                @csrf
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-surface-500 uppercase mb-1">Name</label>
                        <input type="text" name="name" x-model="editingFeed.name" required
                            class="w-full px-3 py-2 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-sm focus:outline-none focus:border-primary-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-surface-500 uppercase mb-1">Folder</label>
                        <select name="folder_id" x-model="editingFeed.folder_id"
                            class="w-full px-3 py-2 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-sm focus:outline-none focus:border-primary-500 text-surface-700 dark:text-surface-200">
                            <option value="">Uncategorized</option>
                            @auth
                                @foreach(auth()->user()->folders as $f)
                                    <option value="{{ $f->id }}">{{ $f->name }}</option>
                                @endforeach
                            @endauth
                        </select>
                    </div>
                </div>
                <div class="flex justify-between items-center mt-6">
                    <button type="button" @click="$el.closest('form').nextElementSibling.submit()" class="text-red-500 hover:text-red-600 text-sm font-medium">Delete Feed</button>
                    <div class="flex gap-2">
                        <button type="button" @click="openEditModal = false" class="px-3 py-2 text-sm text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-white">Cancel</button>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium">Save</button>
                    </div>
                </div>
            </form>
            <form :action="'/feeds/' + editingFeed.id" method="POST" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <!-- Add Source Modal -->
    <div x-show="openAddModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="openAddModal = false"></div>
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6 border border-surface-200 dark:border-surface-800">
            <h3 class="text-xl font-bold mb-2 dark:text-white">Follow a Website</h3>
            <p class="text-surface-500 text-sm mb-6">Enter a URL to follow. We'll find the feed or set up an AI reader for you.</p>
            <form action="{{ route('feeds.store') }}" method="POST">
                @csrf
                <div class="relative">
                    <ion-icon name="link-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                    <input type="url" name="url" placeholder="https://theverge.com" required autofocus
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="openAddModal = false" class="px-4 py-2 text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-white font-medium">Cancel</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">Follow</button>
                </div>
            </form>
        </div>
    </div>

    <!-- YouTube Import Modal -->
    <div x-show="openVideoModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="openVideoModal = false"></div>
        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6 border border-surface-200 dark:border-surface-800">
            <h3 class="text-xl font-bold mb-2 dark:text-white">Summarize Video</h3>
            <p class="text-surface-500 text-sm mb-6">Enter a YouTube URL. AI will generate a summary and transcript.</p>
            <form action="{{ route('youtube.import') }}" method="POST">
                @csrf
                <div class="relative">
                    <ion-icon name="logo-youtube" class="absolute left-3 top-1/2 -translate-y-1/2 text-red-500"></ion-icon>
                    <input type="url" name="url" placeholder="https://youtube.com/watch?v=..." required autofocus
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 transition-all">
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="openVideoModal = false" class="px-4 py-2 text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-white font-medium">Cancel</button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">Summarize</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expose Edit Function globally -->
    <script>
        function editFeed(id, name, folderId) {
            window.dispatchEvent(new CustomEvent('edit-feed', { detail: { id, name, folder_id: folderId } }));
        }
    </script>

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-surface-900/50 backdrop-blur-sm z-30 lg:hidden"
         @click="sidebarOpen = false"></div>

    <!-- Sidebar -->
    <aside :class="{
                'translate-x-0': sidebarOpen,
                '-translate-x-full lg:translate-x-0': !sidebarOpen,
                'w-64': !sidebarMinimized,
                'w-20': sidebarMinimized
            }"
           class="fixed inset-y-0 left-0 bg-white dark:bg-surface-900 flex flex-col z-40 transition-all duration-300 ease-in-out border-r border-surface-200 dark:border-surface-800 lg:static lg:inset-auto h-full">
        
        <div class="h-16 flex items-center justify-between px-6 border-b border-surface-200 dark:border-surface-800 overflow-hidden">
            <div class="flex items-center gap-3 text-primary-600 dark:text-primary-400 min-w-max">
                <ion-icon name="newspaper-outline" class="text-2xl flex-shrink-0"></ion-icon>
                <span x-show="!sidebarMinimized" x-transition.opacity class="text-xl font-bold tracking-tight">ReaderNews</span>
            </div>
            <button @click="toggleSidebarMinimized()" class="hidden lg:flex text-surface-400 hover:text-primary-600 transition-colors">
                <ion-icon :name="sidebarMinimized ? 'chevron-forward-outline' : 'chevron-back-outline'" class="text-xl"></ion-icon>
            </button>
            <button @click="sidebarOpen = false" class="lg:hidden text-surface-400 hover:text-red-500 transition-colors">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6 scrollbar-hide">
            <!-- Main Sections -->
            <div>
                <div x-show="!sidebarMinimized" class="px-3 mb-2 text-xs font-semibold text-surface-400 uppercase tracking-wider">Library</div>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" 
                       title="All Articles"
                       class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <div class="flex items-center gap-3">
                            <ion-icon name="grid-outline" class="text-lg flex-shrink-0"></ion-icon>
                            <span x-show="!sidebarMinimized" class="truncate">All Articles</span>
                        </div>
                        @if(auth()->check() && auth()->user()->unreadArticlesCount() > 0)
                            <span x-show="!sidebarMinimized" class="text-xs text-surface-400">{{ auth()->user()->unreadArticlesCount() }}</span>
                        @endif
                    </a>
                    <a href="{{ route('saved') }}" 
                       title="Saved for Later"
                       class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('saved') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <div class="flex items-center gap-3 font-medium">
                            <ion-icon name="bookmark-outline" class="text-lg flex-shrink-0"></ion-icon>
                            <span x-show="!sidebarMinimized" class="truncate">Saved</span>
                        </div>
                        @if(auth()->check() && auth()->user()->savedArticlesCount() > 0)
                            <span x-show="!sidebarMinimized" class="text-xs text-surface-400">{{ auth()->user()->savedArticlesCount() }}</span>
                        @endif
                    </a>
                    <a href="{{ route('favorites') }}" 
                       title="Favorites"
                       class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('favorites') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <div class="flex items-center gap-3">
                            <ion-icon name="star-outline" class="text-lg flex-shrink-0"></ion-icon>
                            <span x-show="!sidebarMinimized" class="truncate">Favorites</span>
                        </div>
                        @if(auth()->check() && auth()->user()->favoriteArticlesCount() > 0)
                             <span x-show="!sidebarMinimized" class="text-xs text-surface-400">{{ auth()->user()->favoriteArticlesCount() }}</span>
                        @endif
                    </a>
                    <a href="{{ route('notes.index') }}" 
                       title="My Notes"
                       class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('notes.index') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <div class="flex items-center gap-3">
                            <ion-icon name="pencil-outline" class="text-lg flex-shrink-0"></ion-icon>
                            <span x-show="!sidebarMinimized" class="truncate">My Notes</span>
                        </div>
                    </a>
                    <a href="{{ route('research.index') }}" 
                       title="AI Researcher"
                       class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('research.index') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <div class="flex items-center gap-3">
                            <ion-icon name="flask-outline" class="text-lg flex-shrink-0"></ion-icon>
                            <span x-show="!sidebarMinimized" class="truncate">AI Researcher</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Folders/Feeds -->
            <div x-show="!sidebarMinimized" x-transition.opacity>
                <div class="flex items-center justify-between px-3 mb-2 group">
                    <div class="text-xs font-semibold text-surface-400 uppercase tracking-wider">Your Library</div>
                    <div class="flex opacity-0 group-hover:opacity-100 transition-opacity">
                         <button class="text-surface-400 hover:text-red-500 transition-colors mr-2" 
                            title="Summarize Video"
                            @click="openVideoModal = true">
                            <ion-icon name="logo-youtube" class="text-lg"></ion-icon>
                        </button>
                         <button class="text-surface-400 hover:text-primary-600 transition-colors mr-2" 
                            title="Add Feed"
                            @click="openAddModal = true">
                            <ion-icon name="add-circle-outline" class="text-lg"></ion-icon>
                        </button>
                        <button class="text-surface-400 hover:text-primary-600 transition-colors" 
                                title="Create Folder"
                                onclick="document.getElementById('create-folder-form').classList.toggle('hidden')">
                            <ion-icon name="folder-open-outline" class="text-lg"></ion-icon>
                        </button>
                    </div>
                </div>
                
                <form id="create-folder-form" action="{{ route('folders.store') }}" method="POST" class="hidden px-3 mb-2">
                    @csrf
                    <input type="text" name="name" placeholder="Folder Name..." class="w-full px-2 py-1 text-xs rounded border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-800 dark:text-white">
                </form>

                <div class="space-y-4">
                    @auth
                    @foreach(auth()->user()->folders()->with(['feeds' => function($q) { 
                        $q->withCount(['articles' => function($a) {
                            $a->whereDoesntHave('users', function($u) {
                                $u->where('user_id', auth()->id())->where('is_read', true);
                            });
                        }]); 
                    }])->get() as $folder)
                    <div x-data="{ 
                        key: 'folder_{{ $folder->id }}_open',
                        open: false,
                        init() {
                            const stored = localStorage.getItem(this.key);
                            if (stored === null) {
                                this.open = {{ (request()->is('folder/'.$folder->id) || $folder->feeds->contains(fn($f) => request()->is('feed/'.$f->id))) ? 'true' : 'false' }};
                            } else {
                                this.open = stored === 'true';
                            }
                            this.$watch('open', val => localStorage.setItem(this.key, val));
                        }
                    }">
                        <div class="group flex items-center justify-between px-3 py-1.5 text-sm font-medium rounded-lg {{ request()->is('folder/'.$folder->id) ? 'text-primary-600 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400' }} hover:text-surface-900 dark:hover:text-white transition-colors cursor-pointer" @click="open = !open">
                            <div class="flex items-center flex-1 gap-2 truncate">
                                <ion-icon :name="open ? 'chevron-down' : 'chevron-forward'" class="text-xs transition-transform flex-shrink-0"></ion-icon>
                                <span class="truncate">{{ $folder->name }}</span>
                            </div>
                            @if($folder->feeds->sum('articles_count') > 0)
                                <span class="text-xs text-surface-400">{{ $folder->feeds->sum('articles_count') }}</span>
                            @endif
                        </div>
                        
                        <div x-show="open" class="space-y-0.5 ml-2 border-l border-surface-200 dark:border-surface-800 pl-2 mt-1">
                            @foreach($folder->feeds as $feed)
                                <div class="relative group/feed">
                                    <a href="{{ route('feed.show', $feed) }}" class="flex items-center justify-between px-2 py-1.5 text-sm rounded-lg {{ request()->is('feed/'.$feed->id) ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-500 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800' }} transition-colors pr-6">
                                        <span class="truncate">{{ $feed->name }}</span>
                                        @if($feed->articles_count > 0)
                                            <span class="text-[10px] font-semibold text-surface-400 ml-1">{{ $feed->articles_count }}</span>
                                        @endif
                                    </a>
                                    <button @click.prevent="editFeed({{ $feed->id }}, '{{ addslashes($feed->name) }}', '{{ $folder->id }}')" 
                                        class="absolute right-0 top-1/2 -translate-y-1/2 p-1 text-surface-400 hover:text-primary-600 opacity-0 group-hover/feed:opacity-100 transition-opacity">
                                        <ion-icon name="settings-sharp" class="text-[10px]"></ion-icon>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
 
                    @php
                       $uncategorizedFeeds = auth()->user()->feeds()->whereNull('folder_id')->withCount(['articles' => fn($a) => $a->whereDoesntHave('users', fn($u) => $u->where('user_id', auth()->id())->where('is_read', true))])->get();
                    @endphp
                    @foreach($uncategorizedFeeds as $feed)
                    <div class="relative group/feed">
                        <a href="{{ route('feed.show', $feed) }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('feed/'.$feed->id) ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800' }} transition-colors pr-8">
                            <div class="flex items-center gap-3 truncate">
                                @if($feed->favicon)
                                    <img src="{{ $feed->favicon }}" class="w-4 h-4 rounded-sm flex-shrink-0" alt="Icon">
                                @else
                                    <ion-icon name="logo-rss" class="text-lg text-orange-500 flex-shrink-0"></ion-icon>
                                @endif
                                <span class="truncate">{{ $feed->name }}</span>
                            </div>
                            @if($feed->articles_count > 0)
                                <span class="text-xs font-semibold text-surface-400">{{ $feed->articles_count }}</span>
                            @endif
                        </a>
                        <button @click.prevent="editFeed({{ $feed->id }}, '{{ addslashes($feed->name) }}', '')" 
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-surface-400 hover:text-primary-600 opacity-0 group-hover/feed:opacity-100 transition-opacity">
                            <ion-icon name="settings-sharp" class="text-xs"></ion-icon>
                        </button>
                    </div>
                    @endforeach
                    @endauth
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-surface-200 dark:border-surface-800 dark:text-surface-300">
            @auth
            <div class="flex items-center justify-between gap-3 overflow-hidden" x-data="{ open: false }">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-primary-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <div x-show="!sidebarMinimized" class="truncate font-medium text-sm">{{ auth()->user()->name }}</div>
                </div>
                <form x-show="!sidebarMinimized" method="POST" action="{{ route('logout') }}" class="flex-shrink-0">
                    @csrf
                    <button type="submit" class="text-surface-400 hover:text-red-500 transition-colors p-1" title="Log Out">
                        <ion-icon name="log-out-outline" class="text-xl"></ion-icon>
                    </button>
                </form>
            </div>
            @endauth
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 bg-surface-50 dark:bg-surface-950 overflow-hidden relative transition-colors duration-300">
        <!-- Top Header -->
        <header class="h-16 flex items-center justify-between px-4 md:px-6 border-b border-surface-200 dark:border-surface-800 bg-white/80 dark:bg-surface-900/80 backdrop-blur-md sticky top-0 z-10">
            <div class="flex items-center gap-2 md:gap-4 flex-1 min-w-0 pr-2">
                <!-- Mobile Menu Toggle -->
                <button @click="sidebarOpen = true" class="lg:hidden text-2xl text-surface-500 flex-shrink-0 hover:bg-surface-100 dark:hover:bg-surface-800 p-1.5 rounded-lg transition-colors">
                    <ion-icon name="menu-outline"></ion-icon>
                </button>
                
                @if(isset($headerActions))
                    <div class="flex items-center gap-2">
                        {{ $headerActions }}
                    </div>
                @endif

                <!-- Desktop Search -->
                <div class="relative max-w-md w-full hidden lg:block">
                    <form action="{{ route('search') }}" method="GET">
                        <ion-icon name="search-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                        <input type="text" name="q" placeholder="Search..." value="{{ request('q') }}" class="w-full pl-9 pr-4 py-2 rounded-full border border-surface-200 dark:border-surface-700 bg-surface-100 dark:bg-surface-800 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                    </form>
                </div>

                <!-- Breadcrumb Title -->
                @if(isset($pageTitle) && $pageTitle !== 'Unread Articles')
                    <div class="flex items-center gap-1.5 px-2 lg:px-3 py-1 border-l border-surface-200 dark:border-surface-700 ml-1 min-w-0 flex-shrink">
                        @if(isset($feed) && $feed->favicon)
                            <img src="{{ $feed->favicon }}" class="w-3.5 h-3.5 rounded-sm opacity-80 flex-shrink-0" alt="">
                        @else
                            <ion-icon name="{{ isset($folder) ? 'folder-outline' : 'newspaper-outline' }}" class="text-surface-400 text-sm flex-shrink-0 hidden sm:block"></ion-icon>
                        @endif
                        <span class="text-[10px] font-bold text-surface-400 dark:text-surface-500 uppercase tracking-[0.1em] sm:tracking-[0.2em] truncate max-w-[100px] sm:max-w-[200px] lg:max-w-none">
                            {{ $pageTitle }}
                        </span>
                    </div>
                @endif

                <!-- Context Actions (Mark All Read, Add) -->
                @if(isset($contextActions))
                    <div class="flex items-center gap-1.5 md:gap-2 overflow-x-auto no-scrollbar">
                        {{ $contextActions }}
                    </div>
                @endif
            </div>
            
            <div class="flex items-center gap-1 md:gap-2 flex-shrink-0">
                <!-- Theme Toggle -->
                <button class="p-2 rounded-full hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 transition-colors"
                        @click="
                            if (document.documentElement.classList.contains('dark')) {
                                document.documentElement.classList.remove('dark');
                                localStorage.theme = 'light';
                            } else {
                                document.documentElement.classList.add('dark');
                                localStorage.theme = 'dark';
                            }
                        ">
                    <ion-icon name="moon-outline" class="text-xl hidden dark:block"></ion-icon>
                    <ion-icon name="sunny-outline" class="text-xl block dark:hidden"></ion-icon>
                </button>
                
                <!-- Utilities -->
                <form action="{{ route('feeds.refresh-all') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="p-2 rounded-full hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 hover:text-primary-600 transition-colors" title="Refresh All Feeds">
                        <ion-icon name="refresh-outline" class="text-xl"></ion-icon>
                    </button>
                </form>
                <a href="{{ route('ai-configs.index') }}" class="p-2 rounded-full hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 hover:text-primary-600 transition-colors" title="Manage AI Engines">
                    <ion-icon name="hardware-chip-outline" class="text-xl"></ion-icon>
                </a>
                <a href="{{ route('feeds.manage') }}" class="p-2 rounded-full hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 hover:text-primary-600 transition-colors" title="Manage Feeds">
                    <ion-icon name="pulse-outline" class="text-xl"></ion-icon>
                </a>
                <a href="{{ route('profile.edit') }}" class="p-2 rounded-full hover:bg-surface-100 dark:hover:bg-surface-800 text-surface-500 hover:text-primary-600 transition-colors" title="Settings">
                    <ion-icon name="settings-outline" class="text-xl"></ion-icon>
                </a>
            </div>
        </header>

        <!-- Content Scroll Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6 scroll-smooth">
            {{ $slot }}
        </div>
    </main>
</body>
</html>
