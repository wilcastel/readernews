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
    @elseif(!empty($article->image_url))
        <div class="h-64 sm:h-80 w-full relative group">
            <img src="{{ $article->image_url }}" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" alt="Article Header"
                 onerror="this.style.display='none'">
            <div class="absolute inset-0 bg-gradient-to-t from-surface-900/90 via-surface-900/40 to-transparent"></div>
            
            <!-- Download Button -->
            <a href="{{ $article->image_url }}" download target="_blank" 
               class="absolute top-4 right-4 p-2 bg-black/50 hover:bg-black/70 text-white rounded-lg backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
               title="Download Image">
                <ion-icon name="download-outline" class="text-xl"></ion-icon>
            </a>

            <div class="absolute bottom-6 left-6 right-6 text-white">
                <h1 class="text-2xl sm:text-3xl font-bold font-sans leading-tight text-white shadow-black drop-shadow-md">
                    {{ $article->title }}
                </h1>
            </div>
        </div>
    @else
        <div class="p-6 pb-2 border-b border-surface-100 dark:border-surface-800 bg-surface-50 dark:bg-surface-800/50">
             <h1 class="text-2xl sm:text-3xl font-bold font-sans leading-tight text-surface-900 dark:text-white">
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
                 groupedConfigs: [],
                 selectedPrompt: 1,
                 selectedAiConfig: 'round-robin',
                customInstructions: '',
                result: '',
                wpSites: [],
                wpShowPicker: false,
                wpPublishing: false,
                wpPublished: false,
                wpPublish(siteId) {
                    this.wpPublishing = true;
                    this.wpShowPicker = false;
                    fetch('{{ route('articles.publish-to-wordpress', $article) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ wordpress_site_id: siteId, rewritten_content: this.result })
                    })
                    .then(async r => {
                        if (r.status === 409) { this.wpPublished = true; return; }
                        if (!r.ok) { const d = await r.json(); throw new Error(d.message || 'Error'); }
                        this.wpPublished = true;
                    })
                    .catch(e => alert('Error publishing: ' + e.message))
                    .finally(() => { this.wpPublishing = false; });
                },
                init() {
                    fetch('{{ route('wordpress-sites.index') }}', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => { this.wpSites = data.filter(s => s.is_active); });
                     // Fetch prompts
                     fetch('{{ route('prompts.index') }}', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => {
                            this.prompts = data;
                            if(data.length > 0) this.selectedPrompt = data[0].id;
                        });
                     
                     // Fetch AI Configs (active only, sorted by provider)
                      fetch('{{ route('ai-configs.index', ['sort' => 'provider', 'active_only' => 1]) }}', { headers: { 'Accept': 'application/json' } })
                         .then(r => r.json())
                         .then(data => {
                             this.aiConfigs = data;
                             const groups = {};
                             data.forEach(c => {
                                 const key = c.provider;
                                 if (!groups[key]) groups[key] = [];
                                 groups[key].push(c);
                             });
                             this.groupedConfigs = Object.keys(groups).sort().map(k => ({ provider: k, configs: groups[k] }));
                         });
                },
                generate() {
                    this.generating = true;
                        fetch('{{ route('ai.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
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
                        .then(async r => {
                            const data = await r.json();
                            if (!r.ok) throw new Error(data.message || data.error || 'Server Error');
                            return data;
                        })
                        .then(data => {
                            if(data.success) {
                                this.result = data.content;
                            } else {
                                alert('Error: ' + (data.error || 'Unknown error'));
                            }
                        })
                    .catch(e => {
                        alert('System Error: ' + e.message);
                        console.error(e);
                    })
                    .finally(() => {
                         this.generating = false;
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
                                            <template x-for="group in groupedConfigs" :key="group.provider">
                                                <optgroup :label="group.provider.charAt(0).toUpperCase() + group.provider.slice(1)">
                                                    <template x-for="c in group.configs" :key="c.id">
                                                        <option :value="c.id" x-text="c.name"></option>
                                                    </template>
                                                </optgroup>
                                            </template>
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
                                        <button @click="result = ''; wpPublished = false" class="text-xs font-medium text-surface-500 hover:text-surface-700 dark:hover:text-surface-300 px-3 py-1.5 rounded-lg flex items-center gap-2 transition-colors">
                                            <ion-icon name="refresh-outline"></ion-icon> Try Again
                                        </button>
                                        <button @click="navigator.clipboard.writeText(result); alert('Copied!')" class="text-xs font-medium bg-surface-100 dark:bg-surface-800 hover:bg-surface-200 dark:hover:bg-surface-700 text-surface-900 dark:text-white px-3 py-1.5 rounded-lg flex items-center gap-2 transition-colors">
                                            <ion-icon name="copy-outline"></ion-icon> Copy
                                        </button>
                                        <template x-if="wpSites.length > 0">
                                            <div class="relative">
                                                <button @click="wpShowPicker = !wpShowPicker" :disabled="wpPublishing || wpPublished"
                                                        class="text-xs font-medium px-3 py-1.5 rounded-lg flex items-center gap-2 transition-colors disabled:opacity-60"
                                                        :class="wpPublished ? 'bg-green-600 text-white' : 'bg-primary-600 hover:bg-primary-700 text-white'">
                                                    <ion-icon :name="wpPublished ? 'checkmark-outline' : (wpPublishing ? 'reload-outline' : 'cloud-upload-outline')"
                                                              :class="wpPublishing ? 'animate-spin' : ''"></ion-icon>
                                                    <span x-text="wpPublished ? 'Queued!' : 'Publish to WP'"></span>
                                                </button>
                                                <div x-show="wpShowPicker" @click.away="wpShowPicker = false"
                                                     class="absolute right-0 bottom-full mb-1 bg-white dark:bg-surface-800 rounded-xl shadow-xl border border-surface-200 dark:border-surface-700 z-50 min-w-40 py-1">
                                                    <template x-for="site in wpSites" :key="site.id">
                                                        <button @click="wpPublish(site.id)"
                                                                class="w-full text-left px-4 py-2 text-sm text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-700 flex items-center gap-2">
                                                            <ion-icon name="globe-outline" class="text-primary-500 flex-shrink-0"></ion-icon>
                                                            <span x-text="site.name"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
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

            @if(isset($wordPressSites) && $wordPressSites->isNotEmpty())
            <div x-data="{
                showPicker: false,
                publishing: false,
                published: false,
                sites: [],
                init() {
                    fetch('{{ route('wordpress-sites.index') }}', { headers: { 'Accept': 'application/json' } })
                        .then(r => r.json())
                        .then(data => { this.sites = data.filter(s => s.is_active); });
                },
                publish(siteId) {
                    this.publishing = true;
                    this.showPicker = false;
                    fetch('{{ route('articles.publish-to-wordpress', $article) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ wordpress_site_id: siteId })
                    })
                    .then(async r => {
                        if (r.status === 409) { this.published = true; return; }
                        if (!r.ok) { const d = await r.json(); throw new Error(d.message || 'Error'); }
                        this.published = true;
                    })
                    .catch(e => alert('Error publishing: ' + e.message))
                    .finally(() => { this.publishing = false; });
                }
            }" class="relative">
                <button @click="showPicker = !showPicker" :disabled="publishing || published"
                        class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2 disabled:opacity-60"
                        :class="published ? 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20' : 'text-surface-600 dark:text-surface-300 hover:bg-surface-200 dark:hover:bg-surface-700'">
                    <ion-icon :name="published ? 'checkmark-circle-outline' : (publishing ? 'reload-outline' : 'cloud-upload-outline')"
                              :class="publishing ? 'animate-spin' : ''"></ion-icon>
                    <span x-text="published ? 'Queued!' : 'Publish'"></span>
                </button>
                <div x-show="showPicker" @click.away="showPicker = false"
                     class="absolute right-0 mt-1 bg-white dark:bg-surface-800 rounded-xl shadow-xl border border-surface-200 dark:border-surface-700 z-50 min-w-44 py-1">
                    <template x-for="site in sites" :key="site.id">
                        <button @click="publish(site.id)"
                                class="w-full text-left px-4 py-2 text-sm text-surface-700 dark:text-surface-200 hover:bg-surface-100 dark:hover:bg-surface-700 flex items-center gap-2">
                            <ion-icon name="globe-outline" class="text-primary-500 flex-shrink-0"></ion-icon>
                            <span x-text="site.name"></span>
                        </button>
                    </template>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6 md:p-8">
        <div id="modal-article-content" class="prose dark:prose-invert prose-lg max-w-none font-sans text-gray-800 dark:text-gray-200 leading-relaxed
            prose-p:text-lg prose-p:leading-8 prose-p:mb-8 
            prose-headings:font-bold prose-headings:text-gray-900 dark:prose-headings:text-white
            prose-a:text-primary-600 dark:prose-a:text-primary-400 prose-a:no-underline hover:prose-a:underline
            prose-img:hidden
            prose-iframe:w-full prose-iframe:aspect-video prose-iframe:rounded-xl">
            @if($article->content)
                {!! $article->content !!}
            @else
                <div class="text-lg font-sans text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                    {{ $article->summary }}
                </div>
            @endif
        </div>

        <!-- Fetch Button -->
        @if(!str_starts_with($article->url, 'research://'))
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
        @endif
    </div>
</div>

<!-- Social Embeds Script Re-init (Important for modal) -->
<script>
    if(window.twttr) window.twttr.widgets.load(document.getElementById('modal-article-content'));
    if(window.instgrm) window.instgrm.Embeds.process();
</script>
