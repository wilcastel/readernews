<x-layout title="My Notes & Highlights">
    <div class="flex flex-col h-[calc(100vh-theme(spacing.24))]"> <!-- Full height minus header correction -->
        
        <div class="mb-6 flex-shrink-0">
            <h1 class="text-3xl font-bold font-serif text-surface-900 dark:text-white mb-2">My Notes & Highlights</h1>
            <p class="text-surface-500 font-medium">Capture ideas from your reading journey.</p>
        </div>

        <div x-data="{
            query: '',
            notes: [],
            loading: true,
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
                    }
                });
            },
            formatDate(dateStr) {
                 return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            }
        }" 
        x-init="fetchNotes()"
        class="flex flex-col flex-1 min-h-0">

            <!-- Search Bar -->
            <div class="mb-6 flex-shrink-0">
                <div class="relative max-w-lg">
                    <ion-icon name="filter-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-surface-400"></ion-icon>
                    <input type="text" 
                        x-model="query" 
                        @input.debounce.300ms="fetchNotes()"
                        placeholder="Filter by quote, tag, or note..." 
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all shadow-sm">
                </div>
            </div>

            <!-- Notes Grid (Scrollable) -->
            <div class="flex-1 overflow-y-auto pr-2 -mr-2">
                
                <div x-show="loading" class="flex justify-center py-12">
                    <ion-icon name="reload" class="animate-spin text-2xl text-primary-500"></ion-icon>
                </div>

                <div x-show="!loading && notes.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-surface-500">
                    <ion-icon name="document-text-outline" class="text-4xl mb-4 opacity-50"></ion-icon>
                    <p>No notes found. Try adjusting your search or create some highlights while reading!</p>
                </div>

                <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pb-12">
                    <template x-for="note in notes" :key="note.id">
                        <div class="bg-white dark:bg-surface-900 rounded-xl p-5 shadow-sm border border-surface-200 dark:border-surface-800 flex flex-col hover:shadow-md transition-shadow">
                            
                            <!-- Tags -->
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex flex-wrap gap-2">
                                     <template x-for="tag in note.tags">
                                        <span class="text-[10px] uppercase font-bold text-primary-600 bg-primary-50 dark:bg-primary-900/20 px-2 py-1 rounded" x-text="'#' + tag.name"></span>
                                     </template>
                                     <span x-show="note.tags.length === 0" class="text-[10px] uppercase font-bold text-surface-400 bg-surface-100 dark:bg-surface-800 px-2 py-1 rounded">Uncategorized</span>
                                </div>
                                <button @click="deleteNote(note.id)" class="text-surface-400 hover:text-red-500 transition-colors">
                                    <ion-icon name="trash-outline"></ion-icon>
                                </button>
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
                                <span class="text-surface-400" x-text="formatDate(note.created_at)"></span>
                                <a :href="'/articles/' + note.article_id" class="text-primary-600 hover:underline flex items-center gap-1">
                                    Read Article <ion-icon name="arrow-forward-outline"></ion-icon>
                                </a>
                            </div>

                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</x-layout>
