<x-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <div class="mb-8 flex justify-between items-center">
            <div>
                <a href="{{ route('settings.index') }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mb-2 inline-block">&larr; Back to Settings</a>
                <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-white font-bold">API Keys</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Manage API credentials for your AI providers. Multiple accounts per provider supported.</p>
            </div>
            <button onclick="openAccountModal()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                <ion-icon name="add-outline"></ion-icon> Add API Key
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($accounts as $account)
            <div class="bg-white dark:bg-surface-900 rounded-lg border border-slate-200 dark:border-surface-800 shadow-sm p-5">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            <ion-icon name="key-outline" class="text-xl"></ion-icon>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 dark:text-white">{{ $account->label }}</h3>
                            <p class="text-xs text-slate-500 font-mono">{{ $account->provider }}</p>
                            @if($account->email)
                                <p class="text-xs text-slate-400">{{ $account->email }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openAccountModal({{ $account }})" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                            <ion-icon name="create-outline"></ion-icon>
                        </button>
                        <form action="{{ route('provider-accounts.toggle', $account) }}" method="POST">
                            @csrf
                            <button type="submit" class="p-2 {{ $account->is_active ? 'text-green-500 hover:text-green-700' : 'text-red-400 hover:text-red-600' }} transition-colors" title="{{ $account->is_active ? 'Deactivate' : 'Activate' }}">
                                <ion-icon name="{{ $account->is_active ? 'toggle-outline' : 'toggle' }}"></ion-icon>
                            </button>
                        </form>
                        <form action="{{ route('provider-accounts.destroy', $account) }}" method="POST" onsubmit="return confirm('Delete this API key?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    @if($account->base_url)
                        <p class="text-xs text-slate-500 truncate" title="{{ $account->base_url }}">
                            <ion-icon name="link-outline" class="align-middle"></ion-icon> {{ $account->base_url }}
                        </p>
                    @endif
                    <p class="text-xs text-slate-400">
                        <ion-icon name="apps-outline" class="align-middle"></ion-icon>
                        {{ $account->aiConfigs()->count() }} model(s) using this key
                    </p>
                </div>

                <div class="mt-3">
                    <span class="px-2 py-1 rounded-md border text-xs font-semibold {{ $account->is_active ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200' }}">
                        {{ $account->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="col-span-full py-12 text-center">
                <p class="text-slate-500">No API keys configured. Add one to get started.</p>
            </div>
            @endforelse
        </div>

        <!-- Create/Edit Modal -->
        <div id="accountModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden bg-black/50 backdrop-blur-sm" style="display: none;">
            <div class="bg-white dark:bg-surface-900 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-surface-700">
                <form id="accountForm" action="{{ route('provider-accounts.store') }}" method="POST">
                    @csrf
                    <div id="accountMethodField"></div>

                    <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex justify-between items-center bg-surface-50 dark:bg-surface-950">
                        <h3 class="font-bold text-lg dark:text-white" id="accountModalTitle">Add API Key</h3>
                        <button type="button" onclick="closeAccountModal()" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                            <ion-icon name="close" class="text-xl"></ion-icon>
                        </button>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Label</label>
                            <input type="text" name="label" id="accLabel" required placeholder="e.g. Groq - Work Account" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Provider</label>
                                <select name="provider" id="accProvider" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                                    <option value="openai">OpenAI Compatible (Groq, Cerebras, etc)</option>
                                    <option value="openrouter">OpenRouter</option>
                                    <option value="opencode-zen">OpenCode Zen</option>
                                    <option value="opencode-go">OpenCode Go</option>
                                    <option value="ollama">Ollama (Local)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email (optional)</label>
                                <input type="email" name="email" id="accEmail" placeholder="account@email.com" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key</label>
                            <input type="password" name="api_key" id="accApiKey" placeholder="sk-..." class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                            <p class="text-xs text-slate-500 mt-1">Leave empty when editing to keep the current key.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Base URL (auto-filled by provider)</label>
                            <input type="url" name="base_url" id="accBaseUrl" placeholder="https://api.groq.com/openai/v1" class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm">
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="is_active" id="accIsActive" value="1" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <label for="accIsActive" class="text-sm font-medium text-slate-700 dark:text-slate-300">Active</label>
                        </div>
                    </div>

                    <div class="p-4 border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-950 flex justify-end gap-2">
                        <button type="button" onclick="closeAccountModal()" class="px-4 py-2 text-surface-600 dark:text-surface-400 font-medium text-sm hover:text-surface-900 dark:hover:text-white">Cancel</button>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const providerDefaults = {
                'openrouter': 'https://openrouter.ai/api/v1/chat/completions',
                'opencode-zen': 'https://opencode.ai/zen/v1',
                'opencode-go': 'https://opencode.ai/zen/go/v1',
                'ollama': 'http://localhost:11434',
            };

            document.getElementById('accProvider').addEventListener('change', function() {
                const url = providerDefaults[this.value];
                if (url) {
                    document.getElementById('accBaseUrl').value = url;
                }
            });

            function closeAccountModal() {
                document.getElementById('accountModal').style.display = 'none';
            }

            function openAccountModal(account = null) {
                const form = document.getElementById('accountForm');
                form.reset();

                if (account && account.id) {
                    document.getElementById('accountModalTitle').innerText = 'Edit API Key';
                    form.action = '/provider-accounts/' + account.id;
                    document.getElementById('accountMethodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
                    document.getElementById('accLabel').value = account.label;
                    document.getElementById('accProvider').value = account.provider;
                    document.getElementById('accEmail').value = account.email || '';
                    document.getElementById('accApiKey').value = '';
                    document.getElementById('accBaseUrl').value = account.base_url || '';
                    document.getElementById('accIsActive').checked = account.is_active;
                } else {
                    document.getElementById('accountModalTitle').innerText = 'Add API Key';
                    form.action = '{{ route('provider-accounts.store') }}';
                    document.getElementById('accountMethodField').innerHTML = '';
                }

                document.getElementById('accountModal').style.display = 'flex';
            }
        </script>
    </div>
</x-layout>
