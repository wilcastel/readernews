<x-layout title="AI Researcher - ReaderNews">
    <div class="max-w-4xl mx-auto py-12">
        
        <div class="mb-8">
            <h1 class="text-3xl font-bold font-serif text-surface-900 dark:text-white mb-2">AI Researcher</h1>
            <p class="text-surface-500">Investigate a topic by analyzing multiple top-ranking sources.</p>
        </div>

        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Error:</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-white dark:bg-surface-900 rounded-xl shadow-lg border border-surface-200 dark:border-surface-800 p-8">
            <form action="{{ route('research.store') }}" method="POST" 
                  x-data="{ 
                      loading: false, 
                      showManual: false,
                      submit() {
                          this.loading = true;
                          this.$el.submit();
                      }
                  }"
                  @submit.prevent="submit">
                @csrf
                
                <!-- Topic Input -->
                <div class="mb-8">
                    <label for="topic" class="block text-sm font-bold uppercase text-surface-500 mb-2">Research Topic or Keyword</label>
                    <div class="relative">
                        <input type="text" name="topic" id="topic" required
                            class="w-full text-lg px-4 py-4 rounded-xl border border-surface-300 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all shadow-sm"
                            placeholder="e.g., The impact of quantum computing on cybersecurity">
                        <div class="absolute right-4 top-1/2 transform -translate-y-1/2 text-surface-400">
                            <ion-icon name="search-outline" class="text-2xl"></ion-icon>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                     <!-- Limit -->
                    <div>
                        <label for="limit" class="block text-sm font-bold uppercase text-surface-500 mb-2">Number of Sources</label>
                        <select name="limit" id="limit" class="w-full px-4 py-3 rounded-lg border border-surface-300 dark:border-surface-700 bg-white dark:bg-surface-800 dark:text-white focus:ring-primary-500">
                            <option value="1">1 Source</option>
                            <option value="3" selected>3 Sources (Recommended)</option>
                            <option value="5">5 Sources</option>
                            <option value="10">10 Sources (Slow)</option>
                        </select>
                        <p class="text-xs text-surface-500 mt-2">
                            The system will search Google and scrape the top relevant results.
                        </p>
                    </div>

                    <!-- Manual Option Toggle -->
                    <div class="flex items-center">
                        <div class="bg-surface-50 dark:bg-surface-800 p-4 rounded-lg border border-surface-200 dark:border-surface-700 w-full">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" x-model="showManual" class="w-5 h-5 rounded border-surface-300 text-primary-600 focus:ring-primary-500">
                                <span class="text-surface-700 dark:text-surface-300 font-medium">I want to provide specific URLs</span>
                            </label>
                            <p class="text-xs text-surface-500 mt-1 pl-8">
                                Check this to skip Google Search and scrape your own list.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Manual URLs -->
                <div x-show="showManual" x-transition class="mb-8">
                    <label for="urls" class="block text-sm font-bold uppercase text-surface-500 mb-2">Target URLs (Optional)</label>
                    <textarea name="urls" id="urls" rows="4" 
                        class="w-full px-4 py-3 rounded-xl border border-surface-300 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 dark:text-white font-mono text-sm focus:ring-primary-500 placeholder-surface-400"
                        placeholder="https://example.com/article-1&#10;https://another-site.com/news"></textarea>
                    <p class="text-xs text-surface-500 mt-2">Enter one URL per line.</p>
                </div>

                <!-- Action -->
                <div class="border-t border-surface-200 dark:border-surface-700 pt-8 flex items-center justify-end gap-4">
                     <button type="submit" 
                             :disabled="loading"
                             class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-xl shadow-primary-500/20 transition-all transform hover:-translate-y-1 flex items-center gap-3 disable:opacity-75 disabled:cursor-not-allowed">
                        <span x-show="!loading" class="flex items-center gap-2">
                            <ion-icon name="rocket-outline"></ion-icon>
                            Start Investigation
                        </span>
                        <span x-show="loading" class="flex items-center gap-2" style="display: none;">
                            <div class="animate-spin rounded-full h-5 w-5 border-2 border-white border-t-transparent"></div>
                            Analyzing Sources...
                        </span>
                    </button>
                </div>

                <!-- Loading Message -->
                <div x-show="loading" style="display: none;" class="mt-6 text-center">
                    <div class="inline-flex items-center gap-3 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-lg text-sm font-medium animate-pulse">
                        <ion-icon name="information-circle-outline" class="text-lg"></ion-icon>
                        This process may take 1-2 minutes depending on the number of sources.
                    </div>
                </div>

            </form>
        </div>
    </div>
</x-layout>
