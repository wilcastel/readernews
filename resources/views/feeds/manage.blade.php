<x-layout title="Manage Feeds">
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
</x-layout>
