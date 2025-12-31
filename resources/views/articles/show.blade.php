<x-layout title="{{ $article->title }}">
    <x-slot name="headerActions">
        <div class="flex items-center gap-4 mr-4 pr-4 border-r border-surface-200 dark:border-surface-700">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-surface-500 hover:text-surface-900 dark:text-surface-400 dark:hover:text-surface-100 transition-colors font-medium text-sm whitespace-nowrap">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span class="hidden md:inline">Back</span>
            </a>
            
            <div class="flex items-center gap-1 bg-surface-100 dark:bg-surface-800 rounded-lg p-0.5">
                <a href="{{ $previous ? route('articles.show', $previous) : '#' }}" 
                   class="p-1.5 rounded-md hover:bg-white dark:hover:bg-surface-700 transition-colors {{ !$previous ? 'opacity-50 pointer-events-none' : 'text-surface-700 dark:text-surface-200' }}"
                   title="Previous Article">
                   <ion-icon name="chevron-up-outline"></ion-icon>
                </a>
                <a href="{{ $next ? route('articles.show', $next) : '#' }}" 
                   class="p-1.5 rounded-md hover:bg-white dark:hover:bg-surface-700 transition-colors {{ !$next ? 'opacity-50 pointer-events-none' : 'text-surface-700 dark:text-surface-200' }}"
                   title="Next Article">
                   <ion-icon name="chevron-down-outline"></ion-icon>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto bg-white dark:bg-surface-900 shadow-md rounded-2xl overflow-hidden border border-surface-200 dark:border-surface-800"
         x-data="{ 
            fetching: false,
            hasContent: {{ $article->content ? 'true' : 'false' }},
            fetchContent() {
                this.fetching = true;
                fetch('{{ route('articles.fetch', $article) }}', {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.error || 'Server Error');
                    return data;
                })
                .then(data => {
                    if(data.success) {
                        this.hasContent = true;
                        document.getElementById('article-content').innerHTML = data.content;
                        // Re-run embeds
                        if(window.twttr) window.twttr.widgets.load(document.getElementById('article-content'));
                        if(window.instgrm) window.instgrm.Embeds.process();
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                })
                .finally(() => this.fetching = false);
            }
         }">
        
        <!-- Header Image or Video -->
        @php
            $isYoutube = str_contains($article->url, 'youtube.com') || str_contains($article->url, 'youtu.be');
            $videoId = null;
            if ($isYoutube) {
                if (preg_match('/v=([a-zA-Z0-9_-]+)/', $article->url, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $article->url, $matches)) {
                    $videoId = $matches[1];
                }
            }
        @endphp

        @if($isYoutube && $videoId)
             <div class="w-full aspect-video bg-black relative z-10">
                <iframe 
                    class="w-full h-full"
                    src="https://www.youtube.com/embed/{{ $videoId }}?autoplay=0" 
                    title="YouTube video player" 
                    frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="p-8 pb-4 border-b border-surface-100 dark:border-surface-800">
                <div class="flex items-center gap-2 mb-3 text-primary-600 dark:text-primary-400">
                     @if($article->feed->favicon)
                        <img src="{{ $article->feed->favicon }}" class="w-4 h-4 rounded-sm" alt="Favicon">
                    @endif
                    <span class="text-sm font-medium uppercase tracking-wider">{{ $article->feed->name }}</span>
                </div>
                 <h1 class="text-3xl md:text-4xl font-bold font-serif leading-tight text-surface-900 dark:text-white">
                    {{ $article->title }}
                </h1>
            </div>
        
        @elseif($article->image_url)
            <div class="h-64 md:h-80 w-full relative">
                <img src="{{ $article->image_url }}" class="w-full h-full object-cover" alt="Article Header">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                <div class="absolute bottom-6 left-6 right-6 text-white">
                    <div class="flex items-center gap-2 mb-2">
                        @if($article->feed->favicon)
                            <img src="{{ $article->feed->favicon }}" class="w-4 h-4 rounded-sm" alt="Favicon">
                        @endif
                        <span class="text-sm font-medium uppercase tracking-wider">{{ $article->feed->name }}</span>
                    </div>
                    <h1 class="text-3xl md:text-4xl font-bold font-serif leading-tight text-white shadow-black drop-shadow-md">
                        {{ $article->title }}
                    </h1>
                </div>
            </div>
        @else
            <div class="p-8 pb-4 border-b border-surface-100 dark:border-surface-800">
                <div class="flex items-center gap-2 mb-3 text-primary-600 dark:text-primary-400">
                     @if($article->feed->favicon)
                        <img src="{{ $article->feed->favicon }}" class="w-4 h-4 rounded-sm" alt="Favicon">
                    @endif
                    <span class="text-sm font-medium uppercase tracking-wider">{{ $article->feed->name }}</span>
                </div>
                 <h1 class="text-3xl md:text-4xl font-bold font-serif leading-tight text-surface-900 dark:text-white">
                    {{ $article->title }}
                </h1>
            </div>
        @endif

        <div class="px-6 py-4 bg-surface-50 dark:bg-surface-800 flex flex-wrap items-center justify-between gap-4 border-b border-surface-200 dark:border-surface-800"
             x-data="{ 
                @php
                    $userPivot = $article->users->first()?->pivot;
                    $isSaved = $userPivot->is_saved ?? false;
                    $isFavorite = $userPivot->is_favorite ?? false;
                @endphp
                saved: {{ $isSaved ? 'true' : 'false' }},
                favorite: {{ $isFavorite ? 'true' : 'false' }},
                toggleSaved() {
                    this.saved = !this.saved;
                    fetch('/articles/{{ $article->id }}/toggle-saved', { 
                        method: 'POST', 
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
                    });
                },
                toggleFavorite() {
                    this.favorite = !this.favorite;
                    fetch('/articles/{{ $article->id }}/toggle-favorite', { 
                        method: 'POST', 
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } 
                    });
                },
                newTag: '',
                tags: {{ Js::from($article->tags()->where('user_id', auth()->id())->pluck('name')) }},
                addTag() {
                    if (!this.newTag) return;
                    const tagToAdd = this.newTag;
                    this.newTag = ''; // Clear input immediately
                    
                    if (!this.tags.includes(tagToAdd)) {
                        this.tags.push(tagToAdd);
                    }
                    
                     fetch('/articles/{{ $article->id }}/toggle-tag', { 
                        method: 'POST', 
                        headers: { 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ tag_name: tagToAdd })
                    })
                    .then(r => r.json())
                    .then(data => {
                        // Optional: sync state if needed
                    });
                }
             }">
            <div class="flex items-center gap-4 text-sm text-surface-500 dark:text-surface-400">
                <span>{{ $article->author ?? 'Unknown Author' }}</span>
                <span>•</span>
                <span>{{ $article->published_at?->format('F j, Y, g:i a') }}</span>
            </div>

            <!-- Tags UI -->
            <div class="flex items-center gap-2 flex-wrap">
                <template x-for="tag in tags">
                     <a :href="'/tags/' + tag.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '')" class="px-2 py-1 bg-surface-200 dark:bg-surface-700 text-surface-700 dark:text-surface-200 text-xs rounded-full hover:bg-surface-300 dark:hover:bg-surface-600 transition-colors flex items-center gap-1">
                        <ion-icon name="pricetag-outline"></ion-icon>
                        <span x-text="tag"></span>
                     </a>
                </template>
                
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="text-xs text-primary-600 dark:text-primary-400 hover:underline flex items-center gap-1">
                        <ion-icon name="add"></ion-icon> Tag
                    </button>
                    <div x-show="open" @click.outside="open = false" 
                         class="absolute top-full left-0 mt-2 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 rounded-lg shadow-lg p-2 z-20 w-48">
                         <input type="text" x-model="newTag" @keydown.enter="addTag(); open = false" 
                                class="w-full px-2 py-1 text-xs border rounded dark:bg-surface-900 dark:border-surface-600 dark:text-white" 
                                placeholder="New tag..." autofocus>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                 <a href="{{ $article->url }}" target="_blank" class="px-3 py-1.5 rounded-lg text-sm font-medium text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors flex items-center gap-2">
                    <ion-icon name="open-outline"></ion-icon> Visit Original
                </a>
                
                 <button @click="toggleFavorite()" 
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                    :class="favorite ? 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700'">
                    <ion-icon :name="favorite ? 'star' : 'star-outline'"></ion-icon> <span x-text="favorite ? 'Favorited' : 'Favorite'"></span>
                </button>

                 <button @click="toggleSaved()" 
                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                    :class="saved ? 'text-primary-700 bg-primary-100 dark:text-primary-300 dark:bg-primary-900/20' : 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/10 hover:bg-primary-100 dark:hover:bg-primary-900/30'">
                    <ion-icon :name="saved ? 'bookmark' : 'bookmark-outline'"></ion-icon> <span x-text="saved ? 'Saved' : 'Save'"></span>
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="p-6 md:p-10">
            
            <!-- Full Content Placeholder / Display -->
            <div id="article-content" class="prose dark:prose-invert prose-xl max-w-none font-sans leading-relaxed text-surface-800 dark:text-surface-300
                prose-iframe:w-full prose-iframe:aspect-video prose-iframe:rounded-xl prose-img:rounded-xl">
                @if($article->content)
                    {!! $article->content !!}
                @else
                    <div class="text-xl font-sans text-surface-600 mb-8 leading-relaxed">
                        {{ $article->summary }}
                    </div>
                @endif
            </div>

            <!-- Fetch Button (Always visible to allow upgrading content) -->
            <div class="mt-12 text-center py-8 border-t border-dashed border-surface-200">
                <div class="mb-4 text-sm text-surface-500 font-medium" x-show="!hasContent">Viewing summary. Read the full story?</div>
                <div class="mb-4 text-sm text-surface-500 font-medium" x-show="hasContent">Missing something? Try extracting the full article from source.</div>
                
                <button @click="fetchContent()" :disabled="fetching" 
                    class="group relative inline-flex items-center gap-2 px-6 py-3 rounded-xl font-medium shadow-sm transition-all
                           bg-white border border-surface-200 text-surface-700
                           hover:border-primary-500 hover:text-primary-600 hover:shadow-md
                           disabled:opacity-50 disabled:cursor-not-allowed">
                    
                    <ion-icon name="flash-outline" class="text-primary-500 group-hover:animate-pulse" x-show="!fetching"></ion-icon>
                    <ion-icon name="reload" class="animate-spin text-primary-500" x-show="fetching"></ion-icon>
                    
                    <span x-text="fetching ? 'Extracting Content...' : (hasContent ? 'Re-Extract Full Content' : 'Load Full Article')"></span>
                </button>
                 <p class="text-[10px] text-surface-400 mt-3 uppercase tracking-wider" x-show="fetching && !{{ $isYoutube ? 'true' : 'false' }}">Powered by Readability Engine</p>
                 
                 @if($isYoutube)
                 <div class="mt-4">
                    <button @click="
                        fetching = true;
                        fetch('/articles/{{ $article->id }}/summarize', {
                             method: 'POST',
                             headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        })
                        .then(r => r.json())
                        .then(data => {
                            if(data.success) {
                                hasContent = true;
                                document.getElementById('article-content').innerHTML = data.content;
                            } else {
                                alert(data.error);
                            }
                        })
                        .catch(e => alert(e))
                        .finally(() => fetching = false)
                    " 
                    class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-xl font-medium shadow-sm transition-all flex items-center justify-center gap-2 mx-auto disabled:opacity-50"
                    :disabled="fetching">
                        <ion-icon name="logo-youtube" x-show="!fetching"></ion-icon>
                        <ion-icon name="reload" class="animate-spin" x-show="fetching"></ion-icon>
                        <span x-text="fetching ? 'Analyzing Video...' : 'Summarize Video with AI'"></span>
                    </button>
                    <p class="text-[10px] text-surface-400 mt-2">Get a reading summary and transcript</p>
                 </div>
                 @endif
            </div>

        </div>
    </div>
    
    <!-- Scripts for Social Embeds -->
    <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
    <script async src="//www.instagram.com/embed.js"></script>
    <script>
        // Re-initialize embeds after dynamic fetch
        document.addEventListener('alpine:init', () => {
            Alpine.effect(() => {
                // When content is loaded dynamically
                const content = document.getElementById('article-content');
                if(content && window.twttr) {
                    window.twttr.widgets.load(content);
                }
                if(content && window.instgrm) {
                    window.instgrm.Embeds.process();
                }
            });
        });
    </script>
</x-layout>
