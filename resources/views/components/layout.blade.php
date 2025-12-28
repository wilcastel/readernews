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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased text-surface-900 dark:text-surface-100 dark:bg-surface-950 flex overflow-hidden"
      x-data="{ 
        openAddModal: false,
        openEditModal: false,
        editingFeed: { id: null, name: '', folder_id: '' }
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
            
            <!-- Hidden Delete Form -->
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

    <!-- Expose Edit Function globally for the Sidebar to call -->
    <script>
        function editFeed(id, name, folderId) {
            window.dispatchEvent(new CustomEvent('edit-feed', { detail: { id, name, folder_id: folderId } }));
        }
    </script>
    
    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 border-r border-surface-200 dark:border-surface-800 bg-white dark:bg-surface-900 flex flex-col z-20 transition-colors duration-300">
        <div class="h-16 flex items-center px-6 border-b border-surface-200 dark:border-surface-800">
            <div class="flex items-center gap-2 text-primary-600 dark:text-primary-400">
                <ion-icon name="newspaper-outline" class="text-2xl"></ion-icon>
                <span class="text-xl font-bold tracking-tight">ReaderNews</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-6">
            <!-- Main Sections -->
            <div>
                <div class="px-3 mb-2 text-xs font-semibold text-surface-400 uppercase tracking-wider">Library</div>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('dashboard') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <ion-icon name="grid-outline" class="text-lg"></ion-icon>
                        All Articles
                    </a>
                    <a href="{{ route('saved') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('saved') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <ion-icon name="bookmark-outline" class="text-lg"></ion-icon>
                        Saved for Later
                    </a>
                    <a href="{{ route('favorites') }}" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg {{ request()->routeIs('favorites') ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800 transition-colors' }}">
                        <ion-icon name="star-outline" class="text-lg"></ion-icon>
                        Favorites
                    </a>
                </div>
            </div>

            <!-- Folders/Feeds -->
            <div>
                <div class="flex items-center justify-between px-3 mb-2 group">
                    <div class="text-xs font-semibold text-surface-400 uppercase tracking-wider">Your Library</div>
                    <div class="flex opacity-0 group-hover:opacity-100 transition-opacity">
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
                
                <!-- Quick Create Folder Form -->
                <form id="create-folder-form" action="{{ route('folders.store') }}" method="POST" class="hidden px-3 mb-2">
                    @csrf
                    <input type="text" name="name" placeholder="Folder Name..." class="w-full px-2 py-1 text-xs rounded border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-800 dark:text-white" onkeydown="if(event.key === 'Enter') this.form.submit()">
                </form>

                <div class="space-y-4">
                    <!-- Folders Loop -->
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
                                // Default logic: open if active
                                this.open = {{ (request()->is('folder/'.$folder->id) || $folder->feeds->contains(fn($f) => request()->is('feed/'.$f->id))) ? 'true' : 'false' }};
                            } else {
                                this.open = stored === 'true';
                            }
                            this.$watch('open', val => localStorage.setItem(this.key, val));
                        }
                    }">
                        <div class="group flex items-center justify-between px-3 py-1.5 text-sm font-medium rounded-lg {{ request()->is('folder/'.$folder->id) ? 'text-primary-600 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400' }} hover:text-surface-900 dark:hover:text-white transition-colors cursor-pointer" @click="open = !open">
                            <div class="flex items-center flex-1 gap-2">
                                <ion-icon :name="open ? 'chevron-down' : 'chevron-forward'" class="text-xs transition-transform"></ion-icon>
                                <a href="{{ route('folder.show', $folder) }}" class="flex-1 hover:underline truncate" @click.stop>
                                    {{ $folder->name }}
                                </a>
                            </div>
                            
                             <form action="{{ route('folders.destroy', $folder) }}" method="POST" class="ml-2 opacity-0 group-hover:opacity-100 transition-opacity" onsubmit="return confirm('Delete folder? Feeds will be uncategorized.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-surface-400 hover:text-red-500"><ion-icon name="trash-outline"></ion-icon></button>
                            </form>
                        </div>
                        
                        <div x-show="open" class="space-y-0.5 ml-2 border-l border-surface-200 dark:border-surface-800 pl-2 mt-1">
                            @foreach($folder->feeds as $feed)
                                <div class="relative group/feed">
                                    <a href="{{ route('feed.show', $feed) }}" class="flex items-center justify-between px-2 py-1.5 text-sm rounded-lg {{ request()->is('feed/'.$feed->id) ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-500 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800' }} transition-colors pr-8">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            @if($feed->favicon)
                                                <img src="{{ $feed->favicon }}" class="w-3.5 h-3.5 rounded-sm flex-shrink-0" alt="Icon" onerror="this.style.display='none'">
                                            @endif
                                            <span class="truncate">{{ $feed->name }}</span>
                                        </div>
                                        @if($feed->articles_count > 0)
                                            <span class="text-[10px] font-semibold text-surface-400 group-hover/feed:text-primary-600">{{ $feed->articles_count }}</span>
                                        @endif
                                    </a>
                                    <button @click.prevent="editFeed({{ $feed->id }}, '{{ addslashes($feed->name) }}', '{{ $folder->id }}')" 
                                        class="absolute right-1 top-1/2 -translate-y-1/2 p-1 text-surface-400 hover:text-surface-600 dark:hover:text-white opacity-0 group-hover/feed:opacity-100 transition-opacity">
                                        <ion-icon name="settings-sharp" class="text-xs"></ion-icon>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    @endauth

                    <!-- Unorganized Feeds -->
                    <div class="space-y-1">
                         @auth
                         @if(auth()->user()->folders()->count() > 0)
                            <div class="px-3 text-[10px] font-semibold text-surface-400 uppercase tracking-wider mt-4">Uncategorized</div>
                         @endif

                         @foreach(auth()->user()->feeds()->whereNull('folder_id')->withCount(['articles' => fn($a) => $a->whereDoesntHave('users', fn($u) => $u->where('user_id', auth()->id())->where('is_read', true))])->get() as $feed)
                        <div class="relative group/feed">
                            <a href="{{ route('feed.show', $feed) }}" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('feed/'.$feed->id) ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800' }} transition-colors pr-8">
                                <div class="flex items-center gap-3 overflow-hidden">
                                    @if($feed->favicon)
                                        <img src="{{ $feed->favicon }}" class="w-4 h-4 rounded-sm flex-shrink-0" alt="Icon" onerror="this.style.display='none'">
                                        <ion-icon name="logo-rss" class="text-lg text-orange-500 hidden" style="display: none;" onerror="this.style.display='block'"></ion-icon>
                                    @else
                                        <ion-icon name="logo-rss" class="text-lg text-orange-500 flex-shrink-0"></ion-icon>
                                    @endif
                                    <span class="truncate">{{ $feed->name }}</span>
                                </div>
                                @if($feed->articles_count > 0)
                                    <span class="bg-surface-200 dark:bg-surface-700 text-surface-600 dark:text-surface-300 py-0.5 px-2 rounded-full text-xs font-semibold group-hover:bg-primary-100 group-hover:text-primary-600 transition-colors">
                                        {{ $feed->articles_count }}
                                    </span>
                                @endif
                            </a>
                            <button @click.prevent="editFeed({{ $feed->id }}, '{{ addslashes($feed->name) }}', '')" 
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-surface-400 hover:text-surface-600 dark:hover:text-white opacity-0 group-hover/feed:opacity-100 transition-opacity">
                                <ion-icon name="settings-sharp" class="text-xs"></ion-icon>
                            </button>
                        </div>
                        @endforeach

                        <!-- Tags Section -->
                        @if(auth()->user()->tags()->count() > 0)
                            <div class="px-3 text-[10px] font-semibold text-surface-400 uppercase tracking-wider mt-6 mb-2">Tags</div>
                            @foreach(auth()->user()->tags as $tag)
                                <div class="group flex items-center justify-between px-3 py-1.5 text-sm font-medium rounded-lg {{ request()->routeIs('tags.show') && request()->route('tag')->id == $tag->id ? 'bg-primary-50 text-primary-700 dark:bg-surface-800 dark:text-primary-400' : 'text-surface-600 dark:text-surface-400 hover:bg-surface-100 dark:hover:bg-surface-800' }} transition-colors">
                                    <a href="{{ route('tags.show', $tag) }}" class="flex items-center gap-2 flex-1 truncate">
                                        <ion-icon name="pricetag-outline" class="text-surface-400"></ion-icon>
                                        {{ $tag->name }}
                                    </a>
                                     <form action="{{ route('tags.destroy', $tag) }}" method="POST" class="opacity-0 group-hover:opacity-100 transition-opacity" onsubmit="return confirm('Delete tag?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-surface-400 hover:text-red-500 p-1"><ion-icon name="close-outline"></ion-icon></button>
                                    </form>
                                </div>
                            @endforeach
                        @endif

                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <div class="p-4 border-t border-surface-200 dark:border-surface-800 dark:text-surface-300">
            @auth
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-primary-500 to-purple-500 flex items-center justify-center text-white font-bold text-xs">
                        {{ substr(auth()->user()->name, 0, 2) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-sm font-medium truncate w-24">{{ auth()->user()->name }}</div>
                    </div>
                </div>
                <!-- Logout Form -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-surface-400 hover:text-red-500 transition-colors p-1" title="Log Out">
                        <ion-icon name="log-out-outline" class="text-xl"></ion-icon>
                    </button>
                </form>
            </div>
            @else
            <div class="flex flex-col gap-2">
                <a href="{{ route('login') }}" class="w-full bg-surface-100 hover:bg-surface-200 text-surface-900 dark:bg-surface-800 dark:hover:bg-surface-700 py-2 rounded-lg text-sm font-medium text-center transition-colors dark:text-white">Log In</a>
                <a href="{{ route('register') }}" class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2 rounded-lg text-sm font-medium text-center transition-colors">Sign Up</a>
            </div>
            @endauth
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col min-w-0 bg-surface-50 dark:bg-surface-950 overflow-hidden relative transition-colors duration-300">
        <!-- Top Header for Search/Actions -->
        <header class="h-16 flex items-center justify-between px-6 border-b border-surface-200 dark:border-surface-800 bg-white/80 dark:bg-surface-900/80 backdrop-blur-md sticky top-0 z-10 transition-colors duration-300">
            <div class="flex items-center gap-4 flex-1">
                <button class="lg:hidden text-2xl text-surface-500">
                    <ion-icon name="menu-outline"></ion-icon>
                </button>
                
                @if(isset($headerActions))
                    {{ $headerActions }}
                @endif

                <div class="relative max-w-md w-full">
                    <ion-icon name="search-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                    <input type="text" placeholder="Search articles..." class="w-full pl-10 pr-4 py-2 rounded-full border border-surface-200 bg-surface-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button class="p-2 rounded-full hover:bg-surface-100 text-surface-500 transition-colors"
                        @click="
                            if (document.documentElement.classList.contains('dark')) {
                                document.documentElement.classList.remove('dark');
                                localStorage.theme = 'light';
                            } else {
                                document.documentElement.classList.add('dark');
                                localStorage.theme = 'dark';
                            }
                        "
                        x-data="{
                            init() {
                                if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                                    document.documentElement.classList.add('dark');
                                } else {
                                    document.documentElement.classList.remove('dark');
                                }
                            }
                        }"
                >
                    <ion-icon name="moon-outline" class="text-xl hidden dark:block"></ion-icon>
                    <ion-icon name="sunny-outline" class="text-xl block dark:hidden"></ion-icon>
                </button>
                <button class="p-2 rounded-full hover:bg-surface-100 text-surface-500 transition-colors">
                    <ion-icon name="refresh-outline" class="text-xl"></ion-icon>
                </button>
                <button class="p-2 rounded-full hover:bg-surface-100 text-surface-500 transition-colors">
                    <ion-icon name="settings-outline" class="text-xl"></ion-icon>
                </button>
            </div>
        </header>

        <!-- Content Scroll Area -->
        <div class="flex-1 overflow-y-auto p-6 scroll-smooth">
            {{ $slot }}
        </div>
    </main>

</body>
</html>
