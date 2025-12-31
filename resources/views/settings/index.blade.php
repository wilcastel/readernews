<x-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl text-slate-800 dark:text-white font-bold">Configuración de IA</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Gestiona el proveedor de Inteligencia Artificial para el scraper.</p>
        </div>

        <div class="bg-white dark:bg-surface-900 rounded-lg border border-slate-200 dark:border-surface-800 shadow-lg p-6 max-w-3xl" 
             x-data="{ 
                provider: '{{ $provider }}',
                isLoading: false,
                isTesting: false,
                fetchedModels: [],
                testResult: null,
                
                async scanModels(currentProvider, url, key = '') {
                    this.isLoading = true;
                    this.fetchedModels = [];
                    try {
                        const response = await fetch('{{ route('settings.fetch-models') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({ 
                                provider: currentProvider, 
                                url: url, 
                                key: key 
                            })
                        });
                        const data = await response.json();
                        if (data.error) {
                            alert('Error: ' + data.error);
                        } else if (data.models && data.models.length > 0) {
                            this.fetchedModels = data.models;
                        } else {
                            alert('No models found.');
                        }
                    } catch (e) {
                         alert('Connection failed.');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async testModel(currentProvider, url, model, key = '') {
                    this.isTesting = true;
                    this.testResult = null;
                    try {
                         const response = await fetch('{{ route('settings.test-model') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({ 
                                provider: currentProvider, 
                                url: url, 
                                model: model,
                                key: key 
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.testResult = { type: 'success', message: data.message };
                        } else {
                            this.testResult = { type: 'error', message: data.error };
                        }
                    } catch (e) {
                         this.testResult = { type: 'error', message: 'Connection failed.' };
                    } finally {
                        this.isTesting = false;
                    }
                }
             }">
            
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf

                <!-- Provider Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Proveedor de IA</label>
                    <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
                        <label class="relative flex-1 cursor-pointer">
                            <input type="radio" name="ai_provider" value="ollama" class="peer sr-only" x-model="provider" @change="fetchedModels = []">
                            <div class="p-4 rounded-lg border border-slate-200 dark:border-surface-700 hover:border-indigo-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 transition-all text-center">
                                <div class="font-bold text-slate-800 dark:text-white">Ollama (Local)</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Gratis, modelos en tu PC</div>
                            </div>
                        </label>
                        <label class="relative flex-1 cursor-pointer">
                            <input type="radio" name="ai_provider" value="openrouter" class="peer sr-only" x-model="provider" @change="fetchedModels = []">
                            <div class="p-4 rounded-lg border border-slate-200 dark:border-surface-700 hover:border-indigo-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 transition-all text-center">
                                <div class="font-bold text-slate-800 dark:text-white">OpenRouter (API)</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Pago, modelos potentes</div>
                            </div>
                        </label>
                        <label class="relative flex-1 cursor-pointer">
                            <input type="radio" name="ai_provider" value="openai" class="peer sr-only" x-model="provider" @change="fetchedModels = []">
                            <div class="p-4 rounded-lg border border-slate-200 dark:border-surface-700 hover:border-indigo-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/20 transition-all text-center">
                                <div class="font-bold text-slate-800 dark:text-white">LM Studio / Generic</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Local (OpenAI API)</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Test Result Feedback -->
                <div x-show="testResult" style="display: none;" class="mb-4 p-4 rounded-md" :class="testResult && testResult.type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'">
                    <div class="flex items-center">
                        <ion-icon :name="testResult && testResult.type === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline'" class="mr-2 text-xl"></ion-icon>
                        <span x-text="testResult ? testResult.message : ''"></span>
                    </div>
                </div>

                <!-- Ollama Settings -->
                <div x-show="provider === 'ollama'" x-transition class="space-y-4" x-data="{ url: '{{ $ollama_url }}', model: '{{ $ollama_model }}' }">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL de Ollama</label>
                            <input type="text" name="ollama_url" x-model="url" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="http://localhost:11434">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre del Modelo</label>
                            <div class="flex gap-2">
                                <input type="text" name="ollama_model" x-model="model" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="e.g. qwen3:4b">
                                <button type="button" @click="scanModels('ollama', url)" class="px-3 py-2 bg-slate-200 dark:bg-surface-700 rounded hover:bg-slate-300 dark:hover:bg-surface-600 transition" title="Escanear modelos" :disabled="isLoading">
                                    <ion-icon :name="isLoading ? 'hourglass-outline' : 'search-outline'"></ion-icon>
                                </button>
                                <button type="button" @click="testModel('ollama', url, model)" class="px-3 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition" title="Probar Modelo" :disabled="isTesting">
                                    <ion-icon :name="isTesting ? 'hourglass-outline' : 'flask-outline'"></ion-icon>
                                </button>
                            </div>
                            <!-- Model Selector -->
                            <div x-show="fetchedModels.length > 0 && provider === 'ollama'" class="mt-2">
                                <select @change="model = $el.value" class="w-full text-sm rounded-md border-gray-300 dark:border-surface-700 bg-slate-50 dark:bg-surface-800 dark:text-white p-2">
                                    <option value="">-- Seleccionar Detectado --</option>
                                    <template x-for="m in fetchedModels">
                                        <option :value="m" x-text="m"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 text-xs p-3 rounded-md">
                        <strong>Nota:</strong> Usaremos el puente Node.js para conectar con tu instancia local de Ollama.
                    </div>
                </div>

                <!-- OpenRouter Settings -->
                <div x-show="provider === 'openrouter'" x-transition class="space-y-4" style="display: none;" x-data="{ key: '{{ $openrouter_key }}', model: '{{ $openrouter_model }}' }">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key de OpenRouter</label>
                        <input type="password" name="openrouter_key" x-model="key" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="sk-or-...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Modelo de OpenRouter</label>
                         <div class="flex gap-2">
                            <input type="text" name="openrouter_model" x-model="model" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="e.g. google/gemini-2.0-flash-exp:free">
                            <button type="button" @click="scanModels('openrouter', '', key)" class="px-3 py-2 bg-slate-200 dark:bg-surface-700 rounded hover:bg-slate-300 dark:hover:bg-surface-600 transition" title="Listar modelos" :disabled="isLoading">
                                <ion-icon :name="isLoading ? 'hourglass-outline' : 'search-outline'"></ion-icon>
                            </button>
                            <button type="button" @click="testModel('openrouter', '', model, key)" class="px-3 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition" title="Probar Modelo" :disabled="isTesting">
                                <ion-icon :name="isTesting ? 'hourglass-outline' : 'flask-outline'"></ion-icon>
                            </button>
                        </div>
                        <div x-show="fetchedModels.length > 0 && provider === 'openrouter'" class="mt-2">
                            <select @change="model = $el.value" class="w-full text-sm rounded-md border-gray-300 dark:border-surface-700 bg-slate-50 dark:bg-surface-800 dark:text-white p-2">
                                <option value="">-- Seleccionar Detectado --</option>
                                <template x-for="m in fetchedModels">
                                    <option :value="m" x-text="m"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                     <div class="bg-indigo-50 dark:bg-indigo-900/20 text-indigo-800 dark:text-indigo-300 text-xs p-3 rounded-md">
                        <strong>Tip:</strong> OpenRouter te permite usar modelos gratuitos como Gemini Flash o Llama 3.
                    </div>
                </div>

                <!-- Generic OpenAI / LM Studio Settings -->
                <div x-show="provider === 'openai'" x-transition class="space-y-4" style="display: none;" x-data="{ url: '{{ $openai_url }}', key: '{{ $openai_key }}', model: '{{ $openai_model }}' }">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">URL Base (Ej: LM Studio)</label>
                        <input type="text" name="openai_url" x-model="url" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="http://localhost:1234/v1/chat/completions">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">API Key (Opcional)</label>
                            <input type="password" name="openai_key" x-model="key" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="lm-studio">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Modelo</label>
                             <div class="flex gap-2">
                                <input type="text" name="openai_model" x-model="model" class="w-full rounded-md border-gray-300 dark:border-surface-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white dark:bg-surface-800 text-slate-900 dark:text-white sm:text-sm p-2 border" placeholder="local-model">
                                <button type="button" @click="scanModels('openai', url, key)" class="px-3 py-2 bg-slate-200 dark:bg-surface-700 rounded hover:bg-slate-300 dark:hover:bg-surface-600 transition" title="Escanear LM Studio" :disabled="isLoading">
                                    <ion-icon :name="isLoading ? 'hourglass-outline' : 'search-outline'"></ion-icon>
                                </button>
                                <button type="button" @click="testModel('openai', url, model, key)" class="px-3 py-2 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition" title="Probar Modelo" :disabled="isTesting">
                                    <ion-icon :name="isTesting ? 'hourglass-outline' : 'flask-outline'"></ion-icon>
                                </button>
                            </div>
                            <div x-show="fetchedModels.length > 0 && provider === 'openai'" class="mt-2">
                                <select @change="model = $el.value" class="w-full text-sm rounded-md border-gray-300 dark:border-surface-700 bg-slate-50 dark:bg-surface-800 dark:text-white p-2">
                                    <option value="">-- Seleccionar Detectado --</option>
                                    <template x-for="m in fetchedModels">
                                        <option :value="m" x-text="m"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>
                     <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 text-xs p-3 rounded-md">
                        <strong>LM Studio:</strong> Inicia el servidor local y usa la URL `http://localhost:1234/v1/chat/completions`.
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition">
                        Guardar Configuración
                    </button>
                </div>

            </form>
        </div>

        <!-- Prompts Management Section -->
        <div class="mt-8 bg-white dark:bg-surface-900 rounded-lg border border-slate-200 dark:border-surface-800 shadow-lg p-6 max-w-3xl"
             x-data="{ 
                editingPrompt: null,
                showModal: false,
                isNew: false,
                openEdit(prompt) {
                    this.editingPrompt = { ...prompt }; // Clone object
                    this.isNew = false;
                    this.showModal = true;
                },
                openCreate() {
                    this.editingPrompt = { name: '', content: '' };
                    this.isNew = true;
                    this.showModal = true;
                }
             }">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 dark:text-white">Prompts de Redacción</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Personaliza los estilos de escritura para la IA.</p>
                </div>
                <button @click="openCreate()" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <ion-icon name="add-outline"></ion-icon> Nuevo Prompt
                </button>
            </div>

            <div class="space-y-4">
                @foreach($prompts as $prompt)
                <div class="border border-slate-200 dark:border-surface-700 rounded-lg p-4 flex justify-between items-start hover:bg-slate-50 dark:hover:bg-surface-800 transition-colors">
                    <div>
                        <h3 class="font-bold text-slate-800 dark:text-white">{{ $prompt->name }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-mono bg-slate-100 dark:bg-surface-950 p-2 rounded max-w-xl truncate">
                            {{ Str::limit($prompt->content, 100) }}
                        </p>
                    </div>
                    <div class="flex gap-2 ml-4">
                        <button @click="openEdit({{ $prompt }})" class="p-2 text-slate-500 hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400 transition-colors" title="Editar">
                            <ion-icon name="create-outline"></ion-icon>
                        </button>
                        @if($prompt->id > 1) <!-- Protect default prompt if desired, or just allow all -->
                        <form action="{{ route('prompts.destroy', $prompt) }}" method="POST" onsubmit="return confirm('¿Eliminar este prompt?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition-colors" title="Eliminar">
                                <ion-icon name="trash-outline"></ion-icon>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Edit/Create Modal -->
            <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
                
                <div class="relative bg-white dark:bg-surface-900 rounded-xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 dark:border-surface-700">
                    <form :action="isNew ? '{{ route('prompts.store') }}' : '/prompts/' + editingPrompt.id" method="POST">
                        @csrf
                        <template x-if="!isNew">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="p-4 border-b border-surface-200 dark:border-surface-700 flex justify-between items-center bg-surface-50 dark:bg-surface-950">
                            <h3 class="font-bold text-lg dark:text-white" x-text="isNew ? 'Crear Nuevo Prompt' : 'Editar Prompt'"></h3>
                            <button type="button" @click="showModal = false" class="text-surface-400 hover:text-surface-600 dark:hover:text-surface-200">
                                <ion-icon name="close" class="text-xl"></ion-icon>
                            </button>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
                                <input type="text" name="name" x-model="editingPrompt.name" required class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm focus:ring-primary-500 focus:border-primary-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Contenido del Prompt 
                                    <span class="text-xs font-normal text-slate-500 ml-1">(Usa <code>@{{content}}</code> donde irá el texto)</span>
                                </label>
                                <textarea name="content" x-model="editingPrompt.content" rows="6" required class="w-full rounded-md border-gray-300 dark:border-surface-700 bg-white dark:bg-surface-800 text-slate-900 dark:text-white p-2 text-sm font-mono focus:ring-primary-500 focus:border-primary-500"></textarea>
                            </div>
                        </div>

                        <div class="p-4 border-t border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-950 flex justify-end gap-2">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-surface-600 dark:text-surface-400 font-medium text-sm hover:text-surface-900 dark:hover:text-white">Cancelar</button>
                            <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</x-layout>
