<x-layout title="Manage Feeds">
    <x-slot name="contextActions">
        <button @click="openAddModal = true" class="cursor-pointer bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium shadow-lg shadow-primary-500/30 flex items-center gap-2 transition-all">
            <ion-icon name="add-outline"></ion-icon>
            <span class="hidden lg:inline">Add Source</span>
            <span class="lg:hidden">Add</span>
        </button>
    </x-slot>
    <div class="max-w-5xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold dark:text-white">Feed Health & Management</h1>
                <p class="text-surface-500 mt-2">Check the status of your subscriptions and configure connection modes.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-lg bg-surface-100 hover:bg-surface-200 dark:bg-surface-800 dark:hover:bg-surface-700 text-surface-600 dark:text-surface-300 font-medium transition-colors">
                &larr; Back to Dashboard
            </a>
        </div>

        @if(session('success'))
            <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white dark:bg-surface-900 rounded-2xl shadow-sm border border-surface-200 dark:border-surface-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-50 dark:bg-surface-800/50 border-b border-surface-200 dark:border-surface-800">
                        <tr>
                            <th class="px-6 py-4 font-semibold text-surface-900 dark:text-white">Feed Name</th>
                            <th class="px-6 py-4 font-semibold text-surface-900 dark:text-white">Source URL</th>
                            <th class="px-6 py-4 font-semibold text-surface-900 dark:text-white">Connection Mode</th>
                            <th class="px-6 py-4 font-semibold text-surface-900 dark:text-white">Last Update</th>
                            <th class="px-6 py-4 font-semibold text-surface-900 dark:text-white text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 dark:divide-surface-800">
                        @foreach($feeds as $feed)
                        <tr class="hover:bg-surface-50 dark:hover:bg-surface-800/50 transition-colors" x-data="{ editing: false, url: '{{ $feed->url }}', name: '{{ addslashes($feed->name) }}', selector: '{{ $feed->selector ?? '' }}' }">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($feed->favicon)
                                        <img src="{{ $feed->favicon }}" class="w-5 h-5 rounded-sm" onerror="this.style.display='none'">
                                    @else
                                        <div class="w-5 h-5 rounded-sm bg-surface-200 dark:bg-surface-700 flex items-center justify-center text-xs">
                                            {{ substr($feed->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div class="font-medium text-surface-900 dark:text-white">
                                        <span x-show="!editing">{{ $feed->name }}</span>
                                        <input x-show="editing" type="text" x-model="name" class="w-full px-2 py-1 text-xs rounded border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900 dark:text-white" placeholder="Feed Name">
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 max-w-xs text-surface-500" title="{{ $feed->url }}">
                                <div class="flex flex-col gap-1">
                                    <span x-show="!editing">{{ $feed->url }}</span>
                                    <input x-show="editing" type="text" x-model="url" class="w-full px-2 py-1 text-xs rounded border border-surface-200 dark:border-surface-700 bg-white dark:bg-surface-900 dark:text-white" placeholder="Feed URL">
                                    
                                    <div x-show="editing" class="mt-1">
                                        <input type="text" x-model="selector" class="w-full px-2 py-1 text-[10px] rounded border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 dark:text-surface-300 placeholder-surface-400" placeholder="Optional CSS Selector (e.g. .news-list)">
                                    </div>
                                    <span x-show="!editing && '{{ $feed->selector }}'" class="text-[10px] text-surface-400 font-mono">{{ $feed->selector }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('feeds.toggle-mode', $feed) }}" method="POST" id="form-{{ $feed->id }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="url" :value="url">
                                    <input type="hidden" name="name" :value="name">
                                    <input type="hidden" name="selector" :value="selector">
                                    <div class="relative inline-flex items-center p-1 rounded-lg bg-surface-100 dark:bg-surface-800 cursor-pointer" @click="if(!editing) { editing = true; }">
                                        
                                        <!-- Mode Switcher (Submit on change handled via UI feedback or separate button) -->
                                        <!-- Actually, let's make it a clean select/toggle when editing -->
                                        <select name="is_rss" 
                                            class="appearance-none bg-transparent border-none text-xs font-semibold px-2 py-1 focus:ring-0 cursor-pointer 
                                            {{ $feed->is_rss ? 'text-orange-600 dark:text-orange-400' : 'text-purple-600 dark:text-purple-400' }}"
                                            onchange="if(confirm('Change connection mode?')) this.form.submit()">
                                            <option value="1" {{ $feed->is_rss ? 'selected' : '' }}>RSS Feed</option>
                                            <option value="0" {{ !$feed->is_rss ? 'selected' : '' }}>AI Scraper</option>
                                        </select>
                                        <div class="pointer-events-none absolute right-1 top-1/2 -translate-y-1/2">
                                            <ion-icon name="chevron-down" class="text-[10px] text-surface-400"></ion-icon>
                                        </div>
                                    </div>
                                    @if(!$feed->is_rss)
                                        <div class="text-[10px] text-surface-400 mt-1">Experimental</div>
                                    @endif
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($feed->last_scraped_at)
                                    <span class="text-xs {{ $feed->last_scraped_at->diffInHours() < 2 ? 'text-green-600 dark:text-green-400' : 'text-surface-500' }}">
                                        {{ $feed->last_scraped_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-xs text-orange-500 font-medium">Never/Pending</span>
                                @endif
                            </td>
                            <td class="text-right px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                     <button x-show="editing" @click="editing = false; document.getElementById('form-{{ $feed->id }}').submit()" class="text-xs bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-md">Save</button>
                                     <button x-show="editing" @click="editing = false; url='{{ $feed->url }}'" class="text-xs text-surface-500 hover:text-surface-700 px-2">Cancel</button>

                                    <!-- Actions for AI Feeds Check/Diagnose -->
                                    @if(!$feed->is_rss)
                                    <button @click="$dispatch('open-diagnosis', { id: {{ $feed->id }}, url: '{{ $feed->url }}' })" class="p-2 text-surface-400 hover:text-purple-600 transition-colors" title="Diagnose AI Extraction">
                                        <ion-icon name="bug-outline" class="text-lg"></ion-icon>
                                    </button>
                                    @endif

                                    <form action="{{ route('feeds.refresh', $feed) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="p-2 text-surface-400 hover:text-primary-600 transition-colors" title="Force Refresh">
                                            <ion-icon name="refresh-outline" class="text-lg"></ion-icon>
                                        </button>
                                    </form>
                                    
                                    <button @click="editing = !editing" class="p-2 text-surface-400 hover:text-surface-600 dark:hover:text-white transition-colors" title="Edit URL">
                                        <ion-icon name="pencil-outline" class="text-lg"></ion-icon>
                                    </button>

                                    <form action="{{ route('feeds.destroy', $feed) }}" method="POST" class="inline" onsubmit="return confirm('Delete this feed?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-surface-400 hover:text-red-500 transition-colors" title="Delete">
                                            <ion-icon name="trash-outline" class="text-lg"></ion-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        
                        @if($feeds->isEmpty())
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-surface-500">
                                You haven't added any feeds yet.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Diagnosis Modal -->
    <div x-data="{ 
        isOpen: false, 
        logs: [], 
        feedUrl: '', 
        isLoading: false,
        diagnose(feedId) {
            this.isLoading = true;
            this.logs = ['Starting diagnosis...'];
            this.isOpen = true;
            fetch(`/feed/${feedId}/diagnose`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                if (data.logs) {
                    this.logs = data.logs;
                } else {
                    this.logs = ['Error: No logs returned.', JSON.stringify(data)];
                }
            })
            .catch(err => {
                this.isLoading = false;
                this.logs.push('Network Error: ' + err);
            });
        } 
    }" 
    @open-diagnosis.window="feedUrl = $event.detail.url; diagnose($event.detail.id)"
    x-show="isOpen" 
    style="display: none;"
    class="fixed inset-0 z-50 overflow-y-auto" 
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 aria-hidden="true" 
                 @click="isOpen = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="isOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-surface-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                
                <div class="bg-white dark:bg-surface-900 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-surface-900 dark:text-white" id="modal-title">
                                Diagnóstico AI: <span x-text="feedUrl" class="text-sm font-normal text-surface-500"></span>
                            </h3>
                            <div class="mt-4">
                                <div class="bg-black text-green-400 font-mono text-xs p-4 rounded-md overflow-x-auto h-96 whitespace-pre-wrap">
                                    <template x-for="log in logs">
                                        <div x-text="log" class="mb-1 border-b border-gray-800 pb-1"></div>
                                    </template>
                                    <div x-show="isLoading" class="animate-pulse mt-2">... Procesando solicitud ...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-surface-50 dark:bg-surface-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="isOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-layout>
