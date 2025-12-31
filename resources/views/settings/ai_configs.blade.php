<x-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mb-2 inline-block">&larr; Back to Settings</a>
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-white font-bold">Manage AI Providers</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Configure Local and Cloud AI models locally.</p>
            </div>
            <button onclick="document.getElementById('createModal').style.display='flex'" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                <ion-icon name="add-outline"></ion-icon> Add Provider
            </button>
        </div>

        <!-- Configurations List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($configs as $config)
            <div class="bg-white dark:bg-surface-900 rounded-lg border border-slate-200 dark:border-surface-800 shadow-sm p-5 relative overflow-hidden group">
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
                    <span class="px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        {{ ucfirst($config->mode) }}
                    </span>
                    <span class="px-2 py-1 rounded-md border border-slate-200 dark:border-slate-700 {{ $config->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                        {{ $config->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-slate-500">No AI providers configured. Add one to get started.</p>
            </div>
            @endforelse
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
                                <select name="mode" id="inputMode" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                                    <option value="local">Local Machine</option>
                                    <option value="free">Free Tier (Round Robin)</option>
                                    <option value="paid">Paid / Production</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Model ID</label>
                            <input type="text" name="model_id" id="inputModelId" required placeholder="e.g. llama3-70b-8192" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Base URL (Optional)</label>
                            <input type="url" name="base_url" id="inputBaseUrl" placeholder="https://api.groq.com/openai/v1" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                            <p class="text-xs text-slate-500 mt-1">Leave empty for standard endpoints (e.g. if using OpenRouter provider).</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key</label>
                            <input type="password" name="api_key" id="inputApiKey" placeholder="sk-..." class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                        </div>

                         <div class="flex items-center gap-2">
                             <input type="checkbox" name="is_active" id="inputIsActive" value="1" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                             <label for="inputIsActive" class="text-sm font-medium text-slate-700 dark:text-slate-300">Enabled</label>
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
            function closeModal() {
                document.getElementById('createModal').style.display = 'none';
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
                document.getElementById('inputBaseUrl').value = config.base_url || '';
                document.getElementById('inputApiKey').value = config.api_key || ''; // Might be hidden in real app
                document.getElementById('inputIsActive').checked = config.is_active;

                document.getElementById('createModal').style.display = 'flex';
            }
            
            // Reset form for create
            // ... (Simple logic simplifies for now)
        </script>
    </div>
</x-layout>
