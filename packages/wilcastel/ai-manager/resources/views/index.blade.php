<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AI Manager') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ 
        showModal: false, 
        editing: null,
        form: {
            name: '',
            provider: 'ollama',
            base_url: '',
            api_key: '',
            model_id: '',
            mode: 'local',
            is_active: true
        },
        openCreate() {
            this.editing = null;
            this.form = { name: '', provider: 'ollama', base_url: '', api_key: '', model_id: '', mode: 'local', is_active: true };
            this.showModal = true;
        },
        openEdit(config) {
            this.editing = config.id;
            this.form = { ...config };
            this.showModal = true;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">AI Providers Configuration</h3>
                        <button @click="openCreate()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Add New Provider
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provider / Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($configs as $config)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $config->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="font-bold">{{ $config->provider }}</div>
                                            <div class="text-xs">{{ $config->model_id }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $config->mode == 'local' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                                {{ ucfirst($config->mode) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <form action="{{ route('ai-manager.toggle', $config->id) }}" method="POST" class="inline">
                                                @csrf 
                                                <button type="submit" class="text-xs font-bold {{ $config->is_active ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ $config->is_active ? 'Active' : 'Inactive' }}
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button @click="openEdit({{ $config }})" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                            
                                            <form action="{{ route('ai-manager.destroy', $config->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No providers configured. Add one to act as fallback/round-robin.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div x-show="showModal" class="fixed z-10 inset-0 overflow-y-auto" style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="editing ? '/ai-manager/'+editing : '/ai-manager'" method="POST">
                        @csrf
                        <input type="hidden" name="_method" :value="editing ? 'PUT' : 'POST'">
                        
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="editing ? 'Edit Provider' : 'New Provider'"></h3>
                            
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                <input x-model="form.name" type="text" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Provider</label>
                                    <select x-model="form.provider" name="provider" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                                        <option value="ollama">Ollama</option>
                                        <option value="openrouter">OpenRouter</option>
                                        <option value="openai">OpenAI Compatible</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Mode</label>
                                    <select x-model="form.mode" name="mode" class="shadow border rounded w-full py-2 px-3 text-gray-700">
                                        <option value="local">Local</option>
                                        <option value="free">Free API</option>
                                        <option value="paid">Paid API</option>
                                        <option value="all">Any</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Model ID</label>
                                <input x-model="form.model_id" type="text" name="model_id" placeholder="e.g. qwen2:0.5b or gpt-4o" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Base URL (Optional)</label>
                                <input x-model="form.base_url" type="text" name="base_url" placeholder="http://localhost:11434" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">API Key</label>
                                <input x-model="form.api_key" type="password" name="api_key" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" x-model="form.is_active" class="form-checkbox">
                                    <span class="ml-2">Active</span>
                                </label>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
