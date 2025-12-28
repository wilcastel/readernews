<x-layout title="Home - ReaderNews">
    <!-- Notifications -->
    @if(session('success'))
        <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-bounce">
            {{ session('success') }}
        </div>
    @endif
    
    @if($errors->any())
        <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
             {{ $errors->first() }}
        </div>
    @endif

    <div>
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold font-serif text-surface-900 dark:text-white mb-1">{{ $pageTitle ?? "Today's Briefing" }}</h1>
                <p class="text-surface-500 font-medium">{{ \Carbon\Carbon::now()->toFormattedDateString() }}</p>
            </div>
            <div class="flex gap-2">
                <button @click="openAddModal = true" class="cursor-pointer bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-primary-500/30 flex items-center gap-2 transition-all">
                    <ion-icon name="add-outline"></ion-icon>
                    Add Source
                </button>
            </div>
        </div>
    </div>

    <!-- Featured / Masonry Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        
        @forelse($articles as $article)
        <article 
            x-data="{ 
                saved: {{ $article->is_saved ? 'true' : 'false' }}, 
                read: {{ $article->is_read ? 'true' : 'false' }},
                toggleSaved() {
                    this.saved = !this.saved;
                    fetch('/articles/{{ $article->id }}/toggle-saved', { 
                        method: 'POST', 
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
                    });
                },
                markRead() {
                    this.read = true;
                    fetch('/articles/{{ $article->id }}/mark-read', { 
                        method: 'POST', 
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
                    });
                }
            }"
            :class="read ? 'opacity-75' : ''"
            class="bg-white dark:bg-surface-900 rounded-xl shadow-sm border border-surface-200 dark:border-surface-800 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group cursor-pointer h-full flex flex-col">
            @if($article->image_url)
            <div class="aspect-video bg-surface-200 dark:bg-surface-800 relative overflow-hidden">
                 <img src="{{ $article->image_url }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" :class="read ? 'grayscale' : ''" alt="Article">
            </div>
            @endif
            <div class="p-5 flex flex-col flex-1">
                <div class="flex items-center gap-2 mb-3">
                    @if($article->feed->favicon)
                        <img src="{{ $article->feed->favicon }}" class="w-4 h-4 rounded-sm" alt="Favicon">
                    @endif
                    <span class="text-xs font-semibold text-primary-600 uppercase tracking-wider truncate max-w-[150px]">{{ $article->feed->name }}</span>
                    <span class="text-xs text-surface-400">• {{ $article->published_at?->diffForHumans() ?? 'Recently' }}</span>
                </div>
                <h2 class="text-xl font-bold font-serif mb-3 text-surface-900 dark:text-white leading-tight group-hover:text-primary-600 transition-colors">
                    <a href="{{ route('articles.show', $article) }}" @click="markRead()">{{ $article->title }}</a>
                </h2>
                <p class="text-surface-500 text-sm line-clamp-3 mb-4 flex-1">
                    {{ Str::limit($article->summary, 150) }}
                </p>
                <div class="flex items-center justify-between pt-4 border-t border-surface-100 dark:border-surface-800">
                    <button @click.stop="toggleSaved()" 
                            class="transition-colors" 
                            :class="saved ? 'text-primary-600' : 'text-surface-400 hover:text-primary-600'" 
                            title="Save">
                        <ion-icon :name="saved ? 'bookmark' : 'bookmark-outline'" class="text-xl"></ion-icon>
                    </button>
                    <button @click.stop="markRead()" 
                            class="transition-colors"
                            :class="read ? 'text-green-500' : 'text-surface-400 hover:text-green-500'" 
                            title="Mark as Read">
                        <ion-icon :name="read ? 'checkmark-circle' : 'checkmark-circle-outline'" class="text-xl"></ion-icon>
                    </button>
                </div>
            </div>
        </article>
        @empty
        <div class="col-span-full py-12 flex flex-col items-center justify-center text-center">
            <div class="w-16 h-16 bg-surface-100 dark:bg-surface-800 rounded-full flex items-center justify-center mb-4 text-surface-400">
                <ion-icon name="newspaper-outline" class="text-3xl"></ion-icon>
            </div>
            <h3 class="text-lg font-medium text-surface-900 dark:text-white">Your feed is empty</h3>
            <p class="text-surface-500 max-w-sm mx-auto mt-2">Add your first source to start reading the latest news.</p>
            <button @click="openAddModal = true" class="mt-6 text-primary-600 hover:text-primary-700 font-medium">Add a Source Now &rarr;</button>
        </div>
        @endforelse

    </div>
</x-layout>
