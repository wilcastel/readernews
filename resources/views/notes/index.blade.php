<x-layout title="My Notes & Highlights">
    <div class="flex flex-col h-[calc(100vh-theme(spacing.24))]"> <!-- Full height minus header correction -->
        
        <div class="mb-6 flex-shrink-0">
            <h1 class="text-3xl font-bold font-serif text-surface-900 dark:text-white mb-2">My Notes & Highlights</h1>
            <p class="text-surface-500 font-medium">Capture ideas from your reading journey.</p>
        </div>

        <div x-data="{
            query: '',
            notes: [],
            selected: [],
            loading: true,
<<<<<<< HEAD
=======
            openImportModal: false,
>>>>>>> myNotesInvestigation
            showAiModal: false,
            prompts: [],
            aiConfigs: [],
            selectedPrompt: 1,
<<<<<<< HEAD
            selectedAiConfig: '',
=======
            selectedAiConfig: 'round-robin',
>>>>>>> myNotesInvestigation
            customInstructions: '',
            result: '',
            generating: false,
            
            init() {
                this.fetchNotes();
                this.fetchPrompts();
                this.fetchAiConfigs();
            },
            
            fetchNotes() {
                this.loading = true;
                fetch(`/notes?q=${this.query}`, {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.notes = data.notes;
                    this.loading = false;
                });
            },

            fetchPrompts() {
                fetch('{{ route('prompts.index') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        this.prompts = data;
                        if(data.length > 0) this.selectedPrompt = data[0].id;
                    });
            },

            fetchAiConfigs() {
                fetch('{{ route('ai-configs.index') }}', { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => this.aiConfigs = data);
            },

            deleteNote(id) {
                if(!confirm('Delete this note permanently?')) return;
                
                fetch(`/notes/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        this.notes = this.notes.filter(n => n.id !== id);
                        this.selected = this.selected.filter(i => i !== id);
                    }
                });
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
                        note_id: this.selected, // Sending Note IDs
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
            },

            formatDate(dateStr) {
                 return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        }" 
        class="flex flex-col flex-1 min-h-0 relative">

<<<<<<< HEAD
            <!-- Search Bar -->
            <div class="mb-6 flex-shrink-0">
                <div class="relative max-w-lg">
=======
             <!-- Search Bar & Actions -->
            <div class="mb-6 flex-shrink-0 flex items-center gap-4">
                <div class="relative max-w-lg flex-1">
>>>>>>> myNotesInvestigation
                    <ion-icon name="filter-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                    <input type="text" 
                        x-model="query" 
                        @input.debounce.300ms="fetchNotes()"
                        placeholder="Filter by quote, tag, or note..." 
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all shadow-sm">
                </div>
<<<<<<< HEAD
=======
                <button @click="openImportModal = true" class="bg-surface-900 dark:bg-white text-white dark:text-surface-900 px-4 py-3 rounded-xl font-bold flex items-center gap-2 hover:opacity-90 transition-opacity shadow-sm whitespace-nowrap">
                    <ion-icon name="add-circle-outline" class="text-xl"></ion-icon>
                    Add Article
                </button>
>>>>>>> myNotesInvestigation
            </div>

            <!-- Notes Grid (Scrollable) -->
            <div class="flex-1 overflow-y-auto pr-2 -mr-2 mb-20">
                
                <div x-show="loading" class="flex justify-center py-12">
                    <ion-icon name="reload" class="animate-spin text-2xl text-primary-500"></ion-icon>
                </div>

                <div x-show="!loading && notes.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-surface-500">
                    <ion-icon name="document-text-outline" class="text-4xl mb-4 opacity-50"></ion-icon>
                    <p>No notes found. Try adjusting your search or create some highlights while reading!</p>
                </div>

                <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-12">
                    <template x-for="note in notes" :key="note.id">
                        <div 
                             :class="{ 'ring-2 ring-primary-500 bg-primary-50 dark:bg-surface-800': selected.includes(note.id) }"
                             class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-200 dark:border-surface-800 flex flex-col hover:shadow-md transition-all relative">
                            
                             <!-- Checkbox -->
                             <div class="absolute top-3 right-3 z-10">
                                <input type="checkbox" :value="note.id" x-model="selected" @click.stop class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
                            </div>

                            <!-- Tags -->
                            <div class="mb-3 pr-8">
                                <div class="flex flex-wrap gap-2">
                                     <template x-for="tag in note.tags">
                                        <span class="text-[10px] uppercase font-bold text-primary-600 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded" x-text="'#' + tag.name"></span>
                                     </template>
                                     <span x-show="note.tags.length === 0" class="text-[10px] uppercase font-bold text-surface-400 bg-surface-100 dark:bg-surface-800 px-2 py-1 rounded">Uncategorized</span>
                                </div>
                            </div>

                            <!-- Quote -->
                            <blockquote class="border-l-4 border-yellow-400 pl-4 mb-4 italic text-surface-700 dark:text-surface-300 font-serif leading-relaxed text-sm">
                                &quot;<span x-text="note.quote"></span>&quot;
                            </blockquote>

                            <!-- My Annotation -->
                            <div x-show="note.annotation" class="mb-4 text-sm text-surface-600 dark:text-surface-400 bg-surface-50 dark:bg-surface-800 rounded-lg p-3">
                                <span class="font-semibold block text-xs text-surface-400 mb-1 uppercase tracking-wider">My Note</span>
                                <span x-text="note.annotation"></span>
                            </div>

                            <!-- Footer / Source -->
                            <div class="mt-auto pt-4 border-t border-surface-100 dark:border-surface-800 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="text-surface-400" x-text="formatDate(note.created_at)"></span>
                                    <button @click="deleteNote(note.id)" class="text-surface-400 hover:text-red-500 transition-colors" title="Delete Note">
                                        <ion-icon name="trash-outline"></ion-icon>
                                    </button>
                                </div>
                                <a :href="'/articles/' + note.article_id" class="text-primary-600 hover:underline flex items-center gap-1">
                                    Read Article <ion-icon name="arrow-forward-outline"></ion-icon>
                                </a>
                            </div>

                        </div>
                    </template>
                </div>
            </div>

            <!-- Floating Bulk Actions Bar -->
            <div x-show="selected.length > 0" 
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
                    Synthesize with AI
                </button>
                
                <button @click="selected = []" class="ml-2 text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                    <ion-icon name="close-circle" class="text-xl"></ion-icon>
                </button>
            </div>

            <!-- Bulk AI Modal -->
            <div x-show="showAiModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showAiModal = false"></div>
                <div class="relative bg-white dark:bg-surface-900 rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden border border-surface-200 dark:border-surface-700">
                    <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex justify-between items-center bg-surface-50 dark:bg-surface-950">
                        <h3 class="font-bold text-lg dark:text-white flex items-center gap-2">
                            <ion-icon name="sparkles" class="text-yellow-500"></ion-icon>
                            Knowledge Synthesizer
                        </h3>
                        <button @click="showAiModal = false" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                            <ion-icon name="close" class="text-xl"></ion-icon>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto flex-1 bg-white dark:bg-surface-900">
                        <div x-show="!result && !generating" class="text-center py-6">
                            <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                                <ion-icon name="bulb-outline" class="text-3xl text-blue-600 dark:text-blue-400"></ion-icon>
                            </div>
                            <h4 class="text-xl font-bold text-surface-900 dark:text-white mb-2">Synthesize <span x-text="selected.length"></span> Notes</h4>
                            <p class="text-surface-600 dark:text-surface-400 mb-6 max-w-md mx-auto">Create new content by connecting the dots between your selected highlights.</p>
                            
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
                                    <label class="block text-xs font-bold uppercase text-surface-500 mb-1">Select Logic</label>
                                    <select x-model="selectedPrompt" class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm py-2">
                                        <template x-for="p in prompts" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                        <label class="block text-xs font-bold uppercase text-surface-500 mb-1">Extra Instructions (Optional)</label>
                                        <textarea x-model="customInstructions" placeholder="e.g. Highlight contradictions..." class="w-full rounded-lg border-surface-200 dark:border-surface-700 dark:bg-surface-800 dark:text-white text-sm p-2 h-20 placeholder-surface-400"></textarea>
                                </div>
                            </div>

                            <button @click="generate()" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-primary-500/30 hover:-translate-y-1">
                                Generate Synthesis
                            </button>
                        </div>
                        <div x-show="generating" class="flex flex-col items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-4 border-surface-100 border-t-primary-600 mb-6"></div>
                            <p class="text-surface-900 dark:text-white font-medium animate-pulse">Running AI Agent...</p> 
                            <p class="text-sm text-surface-500 mt-2">Connecting ideas...</p>
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

<<<<<<< HEAD
=======
            <!-- Web Import Modal -->
            <div x-show="openImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="openImportModal = false"></div>
                <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-xl w-full max-w-md relative z-10 p-6 border border-surface-200 dark:border-surface-800">
                    <h3 class="text-xl font-bold mb-2 dark:text-white">Import Article</h3>
                    <p class="text-surface-500 text-sm mb-6">Save an article from the web for your research.</p>
                    
                    <form action="{{ route('web.import') }}" method="POST">
                        @csrf
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-surface-500 uppercase mb-1">Article URL</label>
                                <div class="relative">
                                    <ion-icon name="link-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                                    <input type="url" name="url" placeholder="https://example.com/article" required autofocus
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-surface-500 uppercase mb-1">Tag (Group)</label>
                                <div class="relative">
                                    <ion-icon name="pricetag-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                                    <input type="text" name="tag_name" placeholder="e.g. Research, AI, History"
                                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button type="button" @click="openImportModal = false" class="px-4 py-2 text-surface-600 dark:text-surface-400 hover:text-surface-900 dark:hover:text-white font-medium">Cancel</button>
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">Import Article</button>
                        </div>
                    </form>
                </div>
            </div>

>>>>>>> myNotesInvestigation
        </div>


    </div>
</x-layout>
