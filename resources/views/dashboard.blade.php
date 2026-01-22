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
                @if(isset($feed))
                <form action="{{ route('feeds.refresh', $feed) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="cursor-pointer bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-200 hover:text-primary-600 dark:hover:text-primary-400 px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
                        <ion-icon name="refresh-outline"></ion-icon>
                        Refresh
                    </button>
                </form>
                <form action="{{ route('feeds.mark-all-read', $feed) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="cursor-pointer bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-200 hover:text-green-600 dark:hover:text-green-400 px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                        Mark All Read
                    </button>
                </form>
                @elseif(isset($folder))
                <form action="{{ route('folders.mark-all-read', $folder) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="cursor-pointer bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 text-surface-700 dark:text-surface-200 hover:text-green-600 dark:hover:text-green-400 px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-all">
                        <ion-icon name="checkmark-done-outline"></ion-icon>
                        Mark All Read
                    </button>
                </form>
                @endif
                <button @click="openAddModal = true" class="cursor-pointer bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-primary-500/30 flex items-center gap-2 transition-all">
                    <ion-icon name="add-outline"></ion-icon>
                    Add Source
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Actions & Grid Wrapper -->
    <div x-data="{ 
        selected: [],
        showAiModal: false,
        toggleSelection(id) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(i => i !== id);
            } else {
                this.selected.push(id);
            }
        },
        hasSelection() { return this.selected.length > 0; }
    }">
    
        <!-- Featured / Masonry Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-20">
            
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
                :class="{'opacity-60 grayscale': read, 'ring-2 ring-primary-500 bg-primary-50 dark:bg-surface-800': selected.includes('{{ $article->id }}') || selected.includes({{ $article->id }})}"
                class="bg-white dark:bg-surface-900 rounded-xl shadow-sm border border-surface-200 dark:border-surface-800 overflow-hidden hover:shadow-lg hover:-translate-y-1 transition-all duration-300 group cursor-pointer h-full flex flex-col relative">
                
                <!-- Selection Checkbox -->
                <div class="absolute top-3 right-3 z-10">
                    <input type="checkbox" value="{{ $article->id }}" x-model="selected" @click.stop class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
                </div>

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
                        <a href="{{ route('articles.show', ['article' => $article->id, 'source' => isset($context) ? $context['source'] : 'dashboard', 'source_id' => isset($context) ? $context['id'] : null]) }}" @click="markRead()">{{ $article->title }}</a>
                    </h2>
                    
                    <!-- Tags on Card -->
                    @php
                        $userTags = $article->tags->where('user_id', auth()->id());
                    @endphp
                    @if($userTags->count() > 0)
                    <div class="flex flex-wrap gap-1 mb-3">
                        @foreach($userTags as $tag)
                            <span class="px-1.5 py-0.5 bg-surface-100 dark:bg-surface-800 text-surface-600 dark:text-surface-400 text-[10px] rounded hover:bg-surface-200 dark:hover:bg-surface-700">
                                #{{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                    @endif

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
                @if(isset($feeds) && $feeds->count() > 0)
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mb-4">
                        <ion-icon name="checkmark-done-outline" class="text-3xl"></ion-icon>
                    </div>
                    <h3 class="text-lg font-medium text-surface-900 dark:text-white">All caught up!</h3>
                    <p class="text-surface-500 max-w-sm mx-auto mt-2">No unread articles in your feed.</p>
                @else
                    <div class="w-16 h-16 bg-surface-100 dark:bg-surface-800 rounded-full flex items-center justify-center mb-4 text-surface-400">
                        <ion-icon name="newspaper-outline" class="text-3xl"></ion-icon>
                    </div>
                    <h3 class="text-lg font-medium text-surface-900 dark:text-white">Your feed is empty</h3>
                    <p class="text-surface-500 max-w-sm mx-auto mt-2">Add your first source to start reading the latest news.</p>
                    <button @click="openAddModal = true" class="mt-6 text-primary-600 hover:text-primary-700 font-medium">Add a Source Now &rarr;</button>
                @endif
            </div>
            @endforelse
        </div>

        <!-- Floating Bulk Actions Bar -->
        <div x-show="hasSelection()" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-y-full opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-full opacity-0"
             class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-40 bg-white dark:bg-surface-800 border border-surface-200 dark:border-surface-700 shadow-2xl rounded-full px-6 py-3 flex items-center gap-4">
            
            <div class="font-bold text-surface-900 dark:text-white flex items-center gap-2 border-r border-surface-200 dark:border-surface-700 pr-4">
                <span class="bg-primary-600 text-white text-xs rounded-full w-6 h-6 flex items-center justify-center" x-text="selected.length"></span>
                Selected
            </div>

            <button @click="showAiModal = true" class="flex items-center gap-2 text-sm font-medium text-surface-700 dark:text-surface-200 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                <ion-icon name="sparkles" class="text-yellow-500 text-lg"></ion-icon>
                AI Rewrite
            </button>
            
            <!-- Other bulk actions could go here (Mark Read, Delete, etc) -->
            
            <button @click="selected = []" class="ml-2 text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                <ion-icon name="close-circle" class="text-xl"></ion-icon>
            </button>
        </div>

        <!-- Bulk AI Modal -->
        <div x-show="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;" 
             x-data="{
                generating: false,
                prompts: [],
                aiConfigs: [],
                selectedPrompt: 1,
                selectedAiConfig: 'round-robin',
                customInstructions: '',
                result: '',
                init() {
                     // Fetch Prompts
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
                            article_id: selected, // Pass array
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
             
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAiModal = false"></div>
            <div class="relative bg-white dark:bg-surface-900 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden border border-surface-200 dark:border-surface-700">
                <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex justify-between items-center bg-surface-50 dark:bg-surface-950">
                    <h3 class="font-bold text-lg dark:text-white flex items-center gap-2">
                        <ion-icon name="sparkles" class="text-yellow-500"></ion-icon>
                        Multi-Article Writer
                    </h3>
                    <button @click="showAiModal = false" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                        <ion-icon name="close" class="text-xl"></ion-icon>
                    </button>
                </div>
                <!-- Modal Content (Reused Logic) -->
                <div class="p-6 overflow-y-auto flex-1 bg-white dark:bg-surface-900">
                    <div x-show="!result && !generating" class="text-center py-6">
                        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                            <ion-icon name="documents-outline" class="text-3xl text-indigo-600 dark:text-indigo-400"></ion-icon>
                        </div>
                        <h4 class="text-xl font-bold text-surface-900 dark:text-white mb-2">Synthesize <span x-text="selected.length"></span> Articles</h4>
                        <p class="text-surface-600 dark:text-surface-400 mb-6 max-w-md mx-auto">Generate a combined summary, report, or analysis from your selected sources.</p>
                        
                        <!-- Options -->
                        <div class="max-w-sm mx-auto space-y-4 mb-6 text-left">
                            
                             <!-- AI Engine Selection -->
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
                                 <textarea x-model="customInstructions" placeholder="e.g. Find common patterns..." class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm p-2 h-20 placeholder-surface-400"></textarea>
                            </div>
                        </div>

                        <button @click="generate()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-primary-500/30 hover:-translate-y-1">
                            Generate Synthesis
                        </button>
                    </div>
                    <div x-show="generating" class="flex flex-col items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-4 border-surface-100 border-t-primary-600 mb-6"></div>
                        <p class="text-surface-900 dark:text-white font-medium animate-pulse">Running AI Agent...</p> 
                        <p class="text-sm text-surface-500 mt-2">Synthesizing multiple sources...</p>
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
</x-layout>
