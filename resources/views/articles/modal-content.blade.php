<div class="bg-white dark:bg-surface-900"
     x-data="{ 
        fetching: false,
        hasContent: {{ $article->content ? 'true' : 'false' }},
        fetchContent() {
            this.fetching = true;
            
            // Determine endpoint based on article type
            @php
                $isYoutube = str_contains($article->url, 'youtube.com') || str_contains($article->url, 'youtu.be');
            @endphp
            
            const endpoint = '{{ $isYoutube ? route('articles.summarize', $article) : route('articles.fetch', $article) }}';

            fetch(endpoint, {
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
                    document.getElementById('modal-article-content').innerHTML = data.content;
                    // Re-run embeds
                    if(window.twttr) window.twttr.widgets.load(document.getElementById('modal-article-content'));
                    if(window.instgrm) window.instgrm.Embeds.process();
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => this.fetching = false);
        }
     }">
    
    <!-- Render Image or Video similar to show.blade.php but simplified for modal -->
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
    @elseif($article->image_url)
        <div class="h-64 w-full relative">
            <img src="{{ $article->image_url }}" class="w-full h-full object-cover" alt="Article Header">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <div class="absolute bottom-6 left-6 right-6 text-white">
                <h1 class="text-2xl font-bold font-serif leading-tight text-white shadow-black drop-shadow-md">
                    {{ $article->title }}
                </h1>
            </div>
        </div>
    @else
        <div class="p-6 pb-2 border-b border-surface-100 dark:border-surface-800">
             <h1 class="text-2xl font-bold font-serif leading-tight text-surface-900 dark:text-white">
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
            }
         }">
        <div class="flex items-center gap-4 text-sm text-surface-500 dark:text-surface-400">
            <span>{{ $article->author ?? 'Unknown Author' }}</span>
            <span>•</span>
            <span>{{ $article->published_at?->format('F j, Y, g:i a') }}</span>
        </div>

        <div class="flex items-center gap-2">
             <a href="{{ $article->url }}" target="_blank" class="px-3 py-1.5 rounded-lg text-sm font-medium text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700 transition-colors flex items-center gap-2">
                <ion-icon name="open-outline"></ion-icon> Visit Original
            </a>

            <!-- AI Writer Button (Modal Version) -->
            <div x-data="{
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
                <div x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
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
                                
                                <div class="max-w-sm mx-auto space-y-4 mb-6 text-left">
                                    <div>
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
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-surface-500 mb-1">Select Prompt</label>
                                        <select x-model="selectedPrompt" class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm py-2">
                                            <template x-for="p in prompts" :key="p.id">
                                                <option :value="p.id" x-text="p.name"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                         <label class="block text-xs font-bold uppercase text-surface-500 mb-1">Extra Instructions (Optional)</label>
                                         <textarea x-model="customInstructions" placeholder="e.g. Focus on the positive aspects..." class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm p-2 h-20 placeholder-surface-400"></textarea>
                                    </div>
                                </div>

                                <button @click="generate()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-primary-500/30 hover:-translate-y-1">
                                    Generate Draft
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
            
             <button @click="toggleFavorite()" 
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                :class="favorite ? 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 'text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700'">
                <ion-icon :name="favorite ? 'star' : 'star-outline'"></ion-icon>
            </button>

             <button @click="toggleSaved()" 
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
                :class="saved ? 'text-primary-700 bg-primary-100 dark:text-primary-300 dark:bg-primary-900/20' : 'text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/10 hover:bg-primary-100 dark:hover:bg-primary-900/30'">
                <ion-icon :name="saved ? 'bookmark' : 'bookmark-outline'"></ion-icon>
            </button>
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6 md:p-8">
        <div id="modal-article-content" class="prose dark:prose-invert prose-lg max-w-none font-sans leading-relaxed text-surface-800 dark:text-surface-300
            prose-iframe:w-full prose-iframe:aspect-video prose-iframe:rounded-xl prose-img:rounded-xl">
            @if($article->content)
                {!! $article->content !!}
            @else
                <div class="text-xl font-sans text-surface-600 mb-8 leading-relaxed">
                    {{ $article->summary }}
                </div>
            @endif
        </div>

        <!-- Fetch Button -->
        <div class="mt-12 text-center py-8 border-t border-dashed border-surface-200">
            <div class="mb-4 text-sm text-surface-500 font-medium" x-show="!hasContent">Viewing summary. Read the full story?</div>
             <button @click="fetchContent()" :disabled="fetching" 
                class="group relative inline-flex items-center gap-2 px-6 py-3 rounded-xl font-medium shadow-sm transition-all
                        bg-white border border-surface-200 text-surface-700
                        hover:border-primary-500 hover:text-primary-600 hover:shadow-md
                        disabled:opacity-50 disabled:cursor-not-allowed">
                
                <ion-icon name="flash-outline" class="text-primary-500 group-hover:animate-pulse" x-show="!fetching"></ion-icon>
                <ion-icon name="reload" class="animate-spin text-primary-500" x-show="fetching"></ion-icon>
                
                <span x-text="fetching ? 'Extracting Content...' : (hasContent ? 'Re-Extract Full Content' : '{{ $isYoutube ? 'Load Transcript & Summary' : 'Load Full Article' }}')"></span>
            </button>
        </div>
    </div>
</div>

<!-- Social Embeds Script Re-init (Important for modal) -->
<script>
    if(window.twttr) window.twttr.widgets.load(document.getElementById('modal-article-content'));
    if(window.instgrm) window.instgrm.Embeds.process();
</script>
