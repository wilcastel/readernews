<x-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mb-2 inline-block">&larr; Back to Settings</a>
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-white font-bold">Manage AI Providers</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Configure Local and Cloud AI models locally.</p>
            </div>
            <div class="flex gap-2 flex-wrap items-center">
                <!-- Search -->
                <div class="relative">
                    <ion-icon name="search-outline" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></ion-icon>
                    <input type="text" id="searchInput" placeholder="Search name, model, provider..." class="pl-9 pr-3 py-2 rounded-lg border border-slate-200 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white text-sm w-56 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-shadow">
                </div>

                <!-- Hide Inactive -->
                <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 cursor-pointer select-none bg-white dark:bg-surface-800 border border-slate-200 dark:border-surface-700 px-3 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-surface-700 transition-colors">
                    <input type="checkbox" id="hideInactive" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span>Active only</span>
                </label>

                <!-- Sort Dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" @click.outside="open = false" class="bg-white dark:bg-surface-800 border border-slate-200 dark:border-surface-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                        <ion-icon name="filter-outline"></ion-icon> Sort
                        <ion-icon name="chevron-down-outline" class="text-xs"></ion-icon>
                    </button>
                    <div x-show="open" class="absolute right-0 mt-2 w-48 bg-white dark:bg-surface-800 rounded-lg shadow-lg border border-slate-200 dark:border-surface-700 z-50 py-1" style="display: none;">
                        <a href="?sort=newest" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 {{ request('sort') == 'newest' ? 'font-bold text-primary-600' : '' }}">Newest First</a>
                        <a href="?sort=oldest" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 {{ request('sort') == 'oldest' ? 'font-bold text-primary-600' : '' }}">Oldest First</a>
                        <a href="?sort=provider" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 {{ request('sort') == 'provider' ? 'font-bold text-primary-600' : '' }}">By Provider</a>
                        <div class="border-t border-slate-200 dark:border-surface-700 my-1"></div>
                        <a href="?sort=free_first" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 {{ request('sort') == 'free_first' ? 'font-bold text-green-600' : '' }}">Free / Local First</a>
                        <a href="?sort=paid_first" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 {{ request('sort') == 'paid_first' ? 'font-bold text-yellow-600' : '' }}">Paid First</a>
                        <a href="?sort=most_efficient" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 {{ request('sort') == 'most_efficient' || !request('sort') ? 'font-bold text-indigo-600' : '' }}">💎 Most Efficient</a>
                    </div>
                </div>
                 <a href="{{ route('ai-configs.export') }}" class="bg-white dark:bg-surface-800 border border-slate-200 dark:border-surface-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <ion-icon name="download-outline"></ion-icon> Export
                </a>
                <a href="{{ route('provider-accounts.index') }}" class="bg-white dark:bg-surface-800 border border-slate-200 dark:border-surface-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <ion-icon name="key-outline"></ion-icon> API Keys
                </a>

                <button onclick="document.getElementById('importFile').click()" class="bg-white dark:bg-surface-800 border border-slate-200 dark:border-surface-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-surface-700 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <ion-icon name="cloud-upload-outline"></ion-icon> Import
                </button>
                <form id="importForm" action="{{ route('ai-configs.import') }}" method="POST" enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="file" name="file" id="importFile" accept=".json" onchange="document.getElementById('importForm').submit()">
                </form>

                <button onclick="openCreateModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <ion-icon name="add-outline"></ion-icon> Add Provider
                </button>
            </div>
        </div>

        <!-- Configurations List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($configs as $config)
            <div class="bg-white dark:bg-surface-900 rounded-lg border border-slate-200 dark:border-surface-800 shadow-sm p-5 relative overflow-hidden group" data-config-card data-name="{{ $config->name }}" data-model="{{ $config->model_id }}" data-provider="{{ $config->provider }}" data-account="{{ $config->providerAccount?->label ?? '' }}" data-active="{{ $config->is_active ? '1' : '0' }}">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                         <div class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            @if(Str::contains(strtolower($config->name), 'openai'))
                                <ion-icon name="logo-electron" class="text-xl"></ion-icon>
                            @elseif($config->mode == 'local')
                                <ion-icon name="laptop-outline" class="text-xl"></ion-icon>
                            @else
                                <ion-icon name="cloud-outline" class="text-xl"></ion-icon>
                            @endif
                         </div>
                         <div>
                              <h3 class="font-bold text-slate-800 dark:text-white">{{ $config->name }}</h3>
                              <p class="text-xs text-slate-500 font-mono">{{ $config->model_id }}</p>
                              @if($config->providerAccount)
                                  <p class="text-xs text-indigo-500 mt-0.5"><ion-icon name="key-outline" class="align-middle text-[10px]"></ion-icon> {{ $config->providerAccount->label }}</p>
                              @endif
                         </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <!-- Edit Button (Trigger Modal) -->
                         <button onclick="openEditModal({{ $config }})" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                            <ion-icon name="create-outline"></ion-icon>
                         </button>
                         
                         <form action="{{ route('ai-configs.destroy', $config) }}" method="POST" onsubmit="return confirm('Delete this provider?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                         </form>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2 text-xs">
                    @php
                        $modeColor = match($config->mode) {
                            'free' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
                            'paid' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800',
                            default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700'
                        };
                    @endphp
                    <span class="px-2 py-1 rounded-md border text-xs font-semibold {{ $modeColor }}">
                        {{ ucfirst($config->mode) }}
                    </span>
                    @if($config->cantaprox > 0)
                        <span class="px-2 py-1 rounded-md bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 font-medium" title="Approximate uses per $10">
                            ~{{ number_format($config->cantaprox) }} uses/$10
                        </span>
                    @endif
                    <span class="px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700 {{ $config->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                        {{ $config->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($config->use_for_scraping)
                    <span class="px-2 py-1 rounded-md border bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800 text-xs font-semibold" title="Used for AI Scraping">
                        🕷 Scraper
                    </span>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-slate-500">No AI providers configured. Add one to get started.</p>
            </div>
            @endforelse
        </div>

        <!-- No search results -->
        <div id="noResults" class="col-span-full py-12 text-center hidden">
            <ion-icon name="search-outline" class="text-4xl text-slate-300 mb-2"></ion-icon>
            <p class="text-slate-400">No providers match your search.</p>
        </div>

        <!-- Create/Edit Modal -->
        <div id="createModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden bg-black/50 backdrop-blur-sm" style="display: none;">
            <div class="bg-white dark:bg-surface-900 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-surface-700">
                <form id="configForm" action="{{ route('ai-configs.store') }}" method="POST">
                    @csrf
                    <div id="methodField"></div> 

                    <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex justify-between items-center bg-surface-50 dark:bg-surface-950">
                        <h3 class="font-bold text-lg dark:text-white" id="modalTitle">Add AI Provider</h3>
                        <button type="button" onclick="closeModal()" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                            <ion-icon name="close" class="text-xl"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Display Name</label>
                            <input type="text" name="name" id="inputName" required placeholder="e.g. Groq Production" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Provider Type</label>
                                <select name="provider" id="inputProvider" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                                    <option value="ollama">Ollama (Local)</option>
                                    <option value="openai">OpenAI Compatible (Groq, etc)</option>
                                    <option value="openrouter">OpenRouter</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Usage Mode</label>
                                <select name="mode" id="inputMode" onchange="calculateApprox()" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                                    <option value="local">Local Machine</option>
                                    <option value="free">Free Tier (Round Robin)</option>
                                    <option value="paid">Paid / Production</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 relative">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Input Price ($/1M)</label>
                                <input type="number" step="0.00000001" name="input_price" id="inputInputPrice" oninput="calculateApprox()" placeholder="0.25" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Output Price ($/1M)</label>
                                <input type="number" step="0.00000001" name="output_price" id="inputOutputPrice" oninput="calculateApprox()" placeholder="0.38" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cant. Aprox ($10)</label>
                                <input type="number" name="cantaprox" id="inputCantAprox" placeholder="Auto-calc" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm bg-slate-50 dark:bg-surface-900">
                            </div>
                            <div class="col-span-3 text-[10px] text-slate-400 text-right" id="calcInfo">
                                Base: 1k tokens in / 800 out per request (based on real usage).
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
                            <textarea name="description" id="inputDescription" rows="2" placeholder="Start typing description..." class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Model ID</label>
                            <input type="text" name="model_id" id="inputModelId" required placeholder="e.g. llama3-70b-8192" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Account</label>
                            <select name="provider_account_id" id="inputAccountId" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                                <option value="">-- No API Key (use defaults) --</option>
                                @foreach(\App\Models\ProviderAccount::where('is_active', true)->orderBy('provider')->orderBy('label')->get() as $acc)
                                    <option value="{{ $acc->id }}" data-provider="{{ $acc->provider }}">{{ $acc->label }} ({{ $acc->provider }})</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-500 mt-1">Select which API key this model uses. <a href="{{ route('provider-accounts.index') }}" class="text-indigo-600 hover:underline">Manage keys &rarr;</a></p>
                        </div>

                         <div class="flex items-center gap-6">
                             <div class="flex items-center gap-2">
                                 <input type="checkbox" name="is_active" id="inputIsActive" value="1" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                 <label for="inputIsActive" class="text-sm font-medium text-slate-700 dark:text-slate-300">Enabled</label>
                             </div>
                             <div class="flex items-center gap-2">
                                 <input type="checkbox" name="use_for_scraping" id="inputUseForScraping" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                 <label for="inputUseForScraping" class="text-sm font-medium text-slate-700 dark:text-slate-300">🕷 Use for AI Scraping</label>
                             </div>
                         </div>
                    </div>

                    <div class="p-4 border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-950 flex justify-end gap-2">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 text-surface-600 dark:text-surface-400 font-medium text-sm hover:text-surface-900 dark:hover:text-white">Cancel</button>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save Provider</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // --- Search + Filter ---
            const searchInput = document.getElementById('searchInput');
            const hideInactive = document.getElementById('hideInactive');
            const configCards = document.querySelectorAll('[data-config-card]');
            const noResults = document.getElementById('noResults');
            let searchTimeout;

            function applyFilters() {
                const q = searchInput.value.toLowerCase().trim();
                const onlyActive = hideInactive.checked;
                let visible = 0;

                configCards.forEach(card => {
                    const name = (card.dataset.name || '').toLowerCase();
                    const model = (card.dataset.model || '').toLowerCase();
                    const provider = (card.dataset.provider || '').toLowerCase();
                    const account = (card.dataset.account || '').toLowerCase();
                    const isActive = card.dataset.active === '1';

                    const textMatch = !q || name.includes(q) || model.includes(q) || provider.includes(q) || account.includes(q);
                    const activeMatch = !onlyActive || isActive;

                    const show = textMatch && activeMatch;
                    card.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                const hasQuery = q || onlyActive;
                noResults.style.display = visible === 0 && hasQuery ? 'flex' : 'none';
            }

            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(applyFilters, 150);
            });

            hideInactive.addEventListener('change', applyFilters);

            function closeModal() {
                document.getElementById('createModal').style.display = 'none';
            }

            function calculateApprox() {
                const mode = document.getElementById('inputMode').value;
                const approxField = document.getElementById('inputCantAprox');

                if (mode === 'free') {
                    approxField.value = 100000;
                    return;
                }
                if (mode !== 'paid') {
                    approxField.value = '';
                    return;
                }

                const inputPrice = parseFloat(document.getElementById('inputInputPrice').value) || 0;
                const outputPrice = parseFloat(document.getElementById('inputOutputPrice').value) || 0;
                const budget = 10;
                
                // Estándar basado en uso real (ReaderNews): ~1000 tokens entrada, ~800 salida
                const avgInputTokens = 1000;
                const avgOutputTokens = 800;
                
                const costPerUse = (inputPrice * avgInputTokens / 1000000) + (outputPrice * avgOutputTokens / 1000000);
                
                if (costPerUse > 0) {
                    const uses = Math.floor(budget / costPerUse);
                    approxField.value = uses;
                }
            }

            function openCreateModal() {
                 document.getElementById('modalTitle').innerText = 'Add AI Provider';
                 document.getElementById('configForm').action = '{{ route('ai-configs.store') }}';
                 document.getElementById('methodField').innerHTML = '';
                 document.getElementById('configForm').reset();
                 document.getElementById('createModal').style.display = 'flex';
            }

            function openEditModal(config) {
                // Populate form
                document.getElementById('modalTitle').innerText = 'Edit Provider';
                document.getElementById('configForm').action = '/ai-configs/' + config.id;
                document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                
                document.getElementById('inputName').value = config.name;
                document.getElementById('inputProvider').value = config.provider;
                document.getElementById('inputMode').value = config.mode;
                document.getElementById('inputModelId').value = config.model_id;
                document.getElementById('inputAccountId').value = config.provider_account_id || '';
                document.getElementById('inputIsActive').checked = config.is_active;
                document.getElementById('inputUseForScraping').checked = config.use_for_scraping;

                // New fields
                document.getElementById('inputInputPrice').value = config.input_price || '';
                document.getElementById('inputOutputPrice').value = config.output_price || '';
                document.getElementById('inputCantAprox').value = config.cantaprox || '';
                document.getElementById('inputDescription').value = config.description || '';

                document.getElementById('createModal').style.display = 'flex';
            }

        </script>
    </div>
</x-layout>
