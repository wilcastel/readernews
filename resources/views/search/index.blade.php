<x-layout title="Search Results">
    <x-slot name="contextActions">
        <button @click="openAddModal = true" class="cursor-pointer bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium shadow-lg shadow-primary-500/30 flex items-center gap-2 transition-all">
            <ion-icon name="add-outline"></ion-icon>
            <span class="hidden lg:inline">Add Source</span>
            <span class="lg:hidden">Add</span>
        </button>
    </x-slot>
    <div class="mb-8">
        <h1 class="text-3xl font-bold font-serif text-surface-900 dark:text-white mb-2">
            Search: "{{ $query }}"
        </h1>
        <p class="text-surface-500 font-medium">
            Found {{ $articles->total() }} results
        </p>
    </div>

    <!-- Results Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($articles as $article)
            @php
                $userPivot = $article->users->first()?->pivot;
                $isSaved = $userPivot->is_saved ?? false;
                $isRead = $userPivot->is_read ?? false;
            @endphp
            <article 
                x-data="{ 
                    saved: {{ $isSaved ? 'true' : 'false' }}, 
                    read: {{ $isRead ? 'true' : 'false' }},
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
                class="bg-white dark:bg-surface-900 rounded-xl shadow-sm border border-surface-200 dark:border-surface-800 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group cursor-pointer h-full flex flex-col">
                
                @if($article->image_url)
                <div class="aspect-video bg-surface-200 dark:bg-surface-800 relative overflow-hidden">
                     <img src="{{ $article->image_url }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Article">
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
                        <a href="{{ route('articles.show', $article) }}">
                            {!! preg_replace('/(' . preg_quote($query, '/') . ')/i', '<span class="bg-yellow-200 dark:bg-yellow-900/50 text-surface-900 dark:text-white rounded px-0.5">$1</span>', e($article->title)) !!}
                        </a>
                    </h2>

                    <p class="text-surface-500 text-sm line-clamp-3 mb-4 flex-1">
                        {!! preg_replace('/(' . preg_quote($query, '/') . ')/i', '<span class="bg-yellow-200 dark:bg-yellow-900/50 text-surface-900 dark:text-white rounded px-0.5">$1</span>', e(Str::limit($article->summary, 150))) !!}
                    </p>

                    <div class="flex items-center justify-between pt-4 border-t border-surface-100 dark:border-surface-800">
                        <button @click.stop="toggleSaved()" 
                                class="transition-colors" 
                                :class="saved ? 'text-primary-600' : 'text-surface-400 hover:text-primary-600'" 
                                title="Save">
                            <ion-icon :name="saved ? 'bookmark' : 'bookmark-outline'" class="text-xl"></ion-icon>
                        </button>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full py-12 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 bg-surface-100 dark:bg-surface-800 rounded-full flex items-center justify-center mb-4 text-surface-400">
                    <ion-icon name="search-outline" class="text-3xl"></ion-icon>
                </div>
                <h3 class="text-lg font-medium text-surface-900 dark:text-white">No matches found</h3>
                <p class="text-surface-500 max-w-sm mx-auto mt-2">Try adjusting your search terms.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8">
        {{ $articles->links() }}
    </div>
</x-layout>
