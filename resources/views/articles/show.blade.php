<x-layout title="{{ $article->title }}">
    <x-slot name="headerActions">
        <div class="flex items-center gap-4 mr-4 pr-4 border-r border-surface-200 dark:border-surface-700">
            <a href="{{ $backUrl ?? route('dashboard') }}" class="inline-flex items-center gap-2 text-surface-500 hover:text-surface-900 dark:text-surface-400 dark:hover:text-surface-100 transition-colors font-medium text-sm whitespace-nowrap">
                <ion-icon name="arrow-back-outline"></ion-icon>
                <span class="hidden md:inline">Back</span>
            </a>
            
            @if($article->feed->user_id === auth()->id())
            <form action="{{ route('articles.destroy', $article) }}" method="POST" onsubmit="return confirm('Delete this article/note permanently?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-surface-500 hover:text-red-500 transition-colors" title="Delete Article">
                    <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                </button>
            </form>
            @endif
            <div class="flex items-center gap-1 bg-surface-100 dark:bg-surface-800 rounded-lg p-0.5">
                <a href="{{ $previous ? route('articles.show', ['article' => $previous->id, 'source' => $source ?? null, 'source_id' => $sourceId ?? null]) : '#' }}" 
                   class="p-1.5 rounded-md hover:bg-white dark:hover:bg-surface-700 transition-colors {{ !$previous ? 'opacity-50 pointer-events-none' : 'text-surface-700 dark:text-surface-200' }}"
                   title="Previous Article">
                   <ion-icon name="chevron-up-outline"></ion-icon>
                </a>
                <a href="{{ $next ? route('articles.show', ['article' => $next->id, 'source' => $source ?? null, 'source_id' => $sourceId ?? null]) : '#' }}" 
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
                    src="https://www.youtube-nocookie.com/embed/{{ $videoId }}?feature=oembed&rel=0" 
                    title="YouTube video player" 
                    frameborder="0" 
                    referrerpolicy="strict-origin-when-cross-origin"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                    allowfullscreen>
                </iframe>
            </div>
            <div class="p-8 pb-4 border-b border-surface-100 dark:border-surface-800">
                <div class="flex items-center gap-2 mb-4 text-primary-600 dark:text-primary-400">
                     @if($article->feed->favicon)
                        <img src="{{ $article->feed->favicon }}" class="w-5 h-5 rounded-sm" alt="Favicon">
                    @endif
                    <span class="text-sm font-bold uppercase tracking-wider">{{ $article->feed->name }}</span>
                </div>
                 <h1 class="text-3xl md:text-5xl font-bold font-sans leading-tight text-surface-900 dark:text-white">
                    {{ $article->title }}
                </h1>
            </div>
        
        @elseif(!empty($article->image_url))
            <div class="h-80 md:h-[500px] w-full relative group">
                <img src="{{ $article->image_url }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Article Header"
                     onerror="this.style.display='none'">
                <div class="absolute inset-0 bg-gradient-to-t from-surface-900/95 via-surface-900/50 to-transparent"></div>
                <div class="absolute bottom-8 left-6 right-6 md:left-10 md:right-10 text-white">
                    <div class="flex items-center gap-3 mb-4 opacity-90">
                        @if($article->feed->favicon)
                            <img src="{{ $article->feed->favicon }}" class="w-5 h-5 rounded-sm bg-white" alt="Favicon">
                        @endif
                        <span class="text-sm font-bold uppercase tracking-wider text-primary-200">{{ $article->feed->name }}</span>
                    </div>
                    <h1 class="text-3xl md:text-5xl font-bold font-sans leading-tight text-white shadow-black drop-shadow-lg">
                        {{ $article->title }}
                    </h1>
                </div>
            </div>
        @else
            <div class="p-8 pb-6 border-b border-surface-100 dark:border-surface-800 bg-surface-50 dark:bg-surface-800/30">
                <div class="flex items-center gap-2 mb-4 text-primary-600 dark:text-primary-400">
                     @if($article->feed->favicon)
                        <img src="{{ $article->feed->favicon }}" class="w-5 h-5 rounded-sm" alt="Favicon">
                    @endif
                    <span class="text-sm font-bold uppercase tracking-wider">{{ $article->feed->name }}</span>
                </div>
                 <h1 class="text-3xl md:text-5xl font-bold font-sans leading-tight text-surface-900 dark:text-white">
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
                  <!-- AI Writer Button -->
                  <div class="flex items-center" x-data="{
                    open: false,
                    generating: false,
                    prompts: [],
                    aiConfigs: [],
                    selectedPrompt: 1,
                    selectedAiConfig: 'round-robin',
                    customInstructions: '',
                    result: '',
                    init() {
                         // Fetch prompts
                         fetch('{{ route('prompts.index') }}', { headers: { 'Accept': 'application/json' } })
                            .then(r => r.json())
                            .then(data => {
                                this.prompts = data;
                                if(data.length > 0) this.selectedPrompt = data[0].id;
                            });
                         
                         // Fetch AI Configs
                         fetch('{{ route('ai-configs.index') }}', { headers: { 'Accept': 'application/json' } })
                            .then(r => r.json())
                            .then(data => this.aiConfigs = data);
                    },
                    generate() {
                        this.generating = true;
                        fetch('{{ route('ai.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                article_id: {{ $article->id }},
                                prompt_id: this.selectedPrompt,
                                ai_config_id: this.selectedAiConfig,
                                custom_instructions: this.customInstructions
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            this.generating = false;
                            if(data.success) {
                                this.result = data.content;
                            } else {
                                alert('Error: ' + data.error);
                            }
                        });
                    }
                }">
                    <button @click="open = true" class="px-3 py-1.5 rounded-lg text-sm font-medium text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors flex items-center gap-2">
                        <ion-icon name="sparkles" class="text-yellow-500"></ion-icon> Rewrite
                    </button>
                    
                    <!-- AI Modal -->
                    <div x-show="open" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>
                        <div class="relative bg-white dark:bg-surface-900 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden border border-surface-200 dark:border-surface-700">
                            <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex justify-between items-center bg-surface-50 dark:bg-surface-950">
                                <h3 class="font-bold text-lg dark:text-white flex items-center gap-2">
                                    <ion-icon name="sparkles" class="text-yellow-500"></ion-icon>
                                    AI Writer
                                </h3>
                                <button @click="open = false" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                                    <ion-icon name="close" class="text-xl"></ion-icon>
                                </button>
                            </div>
                            <div class="p-6 overflow-y-auto flex-1 bg-white dark:bg-surface-900">
                                <div x-show="!result && !generating" class="text-center py-6">
                                    <div class="w-16 h-16 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <ion-icon name="newspaper-outline" class="text-3xl text-yellow-600 dark:text-yellow-400"></ion-icon>
                                    </div>
                                    <h4 class="text-xl font-bold text-surface-900 dark:text-white mb-2">Transform Content</h4>
                                    <p class="text-surface-600 dark:text-surface-400 mb-6 max-w-md mx-auto">Generate a unique piece based on this source using AI.</p>
                                    
                                    <!-- Options -->
                                    <div class="max-w-sm mx-auto space-y-4 mb-6 text-left">
                                        
                                        <!-- AI Engine Selection -->
                                        <div class="mb-4">
                                        <label class="block text-xs font-bold uppercase text-surface-500 mb-1">AI Model Engine</label>
                                        <select x-model="selectedAiConfig" class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm py-2">
                                            <option value="">System Default (Ollama)</option>
                                            <option value="round-robin">🔄 Round Robin (Free Tier)</option>
                                            <optgroup label="My Providers">
                                                <template x-for="c in aiConfigs" :key="c.id">
                                                    <option :value="c.id" x-text="c.name"></option>
                                                </template>
                                            </optgroup>
                                        </select>
                                        
                                        <!-- Cost Indicator -->
                                        <div class="mt-2 min-h-[20px]">
                                            <template x-for="c in aiConfigs" :key="c.id">
                                                <div x-show="c.id == selectedAiConfig && c.cantaprox > 0" class="inline-block transition-all">
                                                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded-md border border-indigo-100 dark:border-indigo-800">
                                                        💎 ~<span x-text="c.cantaprox"></span> articles / $10
                                                    </span>
                                                </div>
                                            </template>
                                            <div x-show="selectedAiConfig === 'round-robin' || !selectedAiConfig" class="text-xs text-green-600 dark:text-green-400 font-medium px-1">
                                                ✅ Free Tier
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-xs font-bold uppercase text-surface-500 mb-1">Select Prompt</label>
                                        <select x-model="selectedPrompt" class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm py-2">
                                            <template x-for="p in prompts" :key="p.id">
                                                <option :value="p.id" x-text="p.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="mb-6">
                                         <label class="block text-xs font-bold uppercase text-surface-500 mb-1">Model Description</label>
                                         <div class="w-full rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800/50 p-3 min-h-[80px] text-sm text-surface-600 dark:text-surface-300">
                                            <template x-for="c in aiConfigs" :key="c.id">
                                                <p x-show="c.id == selectedAiConfig" x-text="c.description || 'No description available for this model.'"></p>
                                            </template>
                                            <p x-show="selectedAiConfig === 'round-robin'">Automatically cycles through available free providers to ensure high availability.</p>
                                            <p x-show="!selectedAiConfig">Standard system default generation.</p>
                                         </div>
                                    </div>

                                    <button @click="generate()" class="w-full bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-primary-500/30 hover:-translate-y-1 flex items-center justify-center gap-2">
                                        <ion-icon name="sparkles"></ion-icon> Generate Rewrite
                                    </button>
                                </div>
                                <div x-show="generating" class="flex flex-col items-center justify-center py-12">
                                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-surface-100 border-t-primary-600 mb-6"></div>
                                    <p class="text-surface-900 dark:text-white font-medium animate-pulse">Running AI Agent...</p> 
                                    <p class="text-sm text-surface-500 mt-2">Writing your draft...</p>
                                </div>
                                <div x-show="result">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-surface-400">Generated Draft</h4>
                                        <div class="flex gap-2">
                                            <button @click="result = ''" class="text-xs font-medium text-surface-500 hover:text-surface-700 dark:hover:text-surface-300 px-3 py-1.5 rounded-lg flex items-center gap-2 transition-colors">
                                                <ion-icon name="refresh-outline"></ion-icon> Try Again
                                            </button>
                                            <button @click="navigator.clipboard.writeText(result); alert('Copied!')" class="text-xs font-medium bg-surface-100 dark:bg-surface-800 hover:bg-surface-200 dark:hover:bg-surface-700 text-surface-900 dark:text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-colors">
                                                <ion-icon name="copy-outline"></ion-icon> Copy
                                            </button>
                                        </div>
                                    </div>
                                    <div class="bg-surface-50 dark:bg-surface-950 p-6 rounded-xl border border-surface-100 dark:border-surface-800 prose dark:prose-invert max-w-none whitespace-pre-wrap leading-relaxed shadow-inner" x-text="result"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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

        <!-- Highlighting & Notes Logic -->
        <div x-data="{
             selectionMenu: { show: false, x: 0, y: 0, text: '' },
             noteForm: { tagName: '', annotation: '' },
             handleSelection(e) {
                const selection = window.getSelection();
                const text = selection.toString().trim();
                
                if (text.length > 0) {
                    const range = selection.getRangeAt(0);
                    const rect = range.getBoundingClientRect();
                    
                    // Show menu above selection
                    this.selectionMenu = {
                        show: true,
                        x: rect.left + (rect.width / 2) - 150, // Center horizontally
                        y: rect.top + window.scrollY - 160, // Above text
                        text: text
                    };
                } else {
                    // Only hide if we aren't clicking inside the menu itself
                    if (!this.$refs.menu.contains(e.target)) {
                         this.selectionMenu.show = false;
                    }
                }
             },
             saveNote() {
                fetch('/articles/{{ $article->id }}/notes', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: JSON.stringify({
                        quote: this.selectionMenu.text,
                        annotation: this.noteForm.annotation,
                        tag_name: this.noteForm.tagName
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        this.selectionMenu.show = false;
                        this.noteForm = { tagName: '', annotation: '' };
                        alert('Note saved to ' + (data.note.tags[0]?.name || 'uncategorized'));
                        // Ideally render highlight permanently here
                    }
                });
             }
        }"
        @mouseup.document="handleSelection">

            <!-- Selection Popover -->
            <div x-ref="menu" x-show="selectionMenu.show" 
                 :style="`top: ${selectionMenu.y}px; left: ${selectionMenu.x}px`"
                 class="absolute z-50 bg-white dark:bg-surface-800 shadow-xl rounded-xl border border-surface-200 dark:border-surface-700 p-4 w-[300px] flex flex-col gap-3"
                 style="display: none;">
                 
                 <div class="text-xs text-surface-500 font-medium uppercase tracking-wider">Save to Personal Notes</div>
                 
                 <div class="bg-surface-50 dark:bg-surface-900 p-2 rounded text-xs italic text-surface-600 dark:text-surface-400 border-l-2 border-primary-500 max-h-20 overflow-y-auto">
                    &quot;<span x-text="selectionMenu.text"></span>&quot;
                 </div>

                 <input type="text" x-model="noteForm.tagName" placeholder="#Tag (e.g., Economics)" 
                        class="w-full text-sm px-3 py-2 rounded-lg border border-surface-200 dark:border-surface-700 dark:bg-surface-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:outline-none transition-all">
                 
                 <button @click="saveNote()" class="bg-primary-600 hover:bg-primary-700 text-white rounded-lg px-4 py-2 text-sm font-medium transition-colors shadow-sm">
                    Save Highlight
                 </button>
            </div>

            <div class="p-6 md:p-10">
                <!-- Content Area -->
                <div id="article-content" class="prose dark:prose-invert prose-xl max-w-none font-sans text-gray-800 dark:text-gray-200 leading-relaxed
                    prose-p:text-xl prose-p:leading-8 prose-p:mb-8 
                    prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
                    prose-a:text-primary-600 dark:prose-a:text-primary-400 prose-a:no-underline hover:prose-a:underline
                    prose-img:hidden
                    prose-iframe:w-full prose-iframe:aspect-video prose-iframe:rounded-xl">
                    @if($article->content)
                        {!! $article->content !!}
                    @else
                        <div class="text-xl font-sans text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                            {{ $article->summary }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

            <!-- Fetch Button (Always visible to allow upgrading content, except for Research reports) -->
            @if(!str_starts_with($article->url, 'research://'))
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
            @endif

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
