<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 mb-4">
                <a href="{{ route('templates.index') }}" class="hover:text-gray-900 dark:hover:text-gray-200">Templates</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-gray-900 dark:text-white font-medium">{{ $template->name }}</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Template Bewerken ✏️</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Wijzig de instellingen en uploads van deze template.</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('templates.update', $template) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                @method('PUT')

                <!-- Template Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Template Naam *
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', $template->name) }}"
                        required
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white transition-colors"
                        placeholder="Bijvoorbeeld: Standaard Template"
                    >
                    @error('name')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Page Size -->
                <div>
                    <label for="page_size" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pagina Formaat
                    </label>
                    <select 
                        id="page_size" 
                        name="page_size" 
                        class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-white transition-colors"
                    >
                        <option value="A4" {{ old('page_size', $template->page_size) === 'A4' ? 'selected' : '' }}>A4 (210 × 297mm)</option>
                        <option value="Letter" {{ old('page_size', $template->page_size) === 'Letter' ? 'selected' : '' }}>Letter (8.5 × 11")</option>
                    </select>
                    @error('page_size')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Logo Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Logo Upload
                    </label>
                    
                    @if($template->logo_path)
                    <!-- Existing Logo -->
                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-4">
                            <img src="{{ Storage::url($template->logo_path) }}" alt="Current logo" class="w-24 h-24 object-contain border border-gray-300 dark:border-gray-600 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Huidige Logo</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ basename($template->logo_path) }}</p>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="remove_logo" 
                                    value="1"
                                    class="w-4 h-4 text-red-600 bg-white border-gray-300 rounded focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600"
                                >
                                <span class="text-sm text-red-600 dark:text-red-400">Verwijder</span>
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center gap-4">
                        <label for="logo" class="flex-1 cursor-pointer">
                            <div class="relative group border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-blue-600 dark:text-blue-400">Upload nieuwe logo</span>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        PNG, JPG tot 5MB
                                    </p>
                                </div>
                                <input 
                                    type="file" 
                                    id="logo" 
                                    name="logo" 
                                    accept="image/png,image/jpeg,image/jpg"
                                    class="hidden"
                                    onchange="previewImage(this, 'logo-preview')"
                                >
                            </div>
                        </label>
                        <!-- Preview -->
                        <div id="logo-preview" class="hidden w-32 h-32 border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-700">
                            <img src="" alt="Logo preview" class="w-full h-full object-contain">
                        </div>
                    </div>
                    @error('logo')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Background Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Achtergrond Upload
                    </label>
                    
                    @if($template->background_path)
                    <!-- Existing Background -->
                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                        <div class="flex items-center gap-4">
                            <img src="{{ Storage::url($template->background_path) }}" alt="Current background" class="w-24 h-32 object-cover border border-gray-300 dark:border-gray-600 rounded-lg">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Huidige Achtergrond</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ basename($template->background_path) }}</p>
                            </div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="remove_background" 
                                    value="1"
                                    class="w-4 h-4 text-red-600 bg-white border-gray-300 rounded focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:border-gray-600"
                                >
                                <span class="text-sm text-red-600 dark:text-red-400">Verwijder</span>
                            </label>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-center gap-4">
                        <label for="background" class="flex-1 cursor-pointer">
                            <div class="relative group border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-blue-600 dark:text-blue-400">Upload nieuwe achtergrond</span>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        PNG, JPG tot 5MB (A4 formaat aanbevolen)
                                    </p>
                                </div>
                                <input 
                                    type="file" 
                                    id="background" 
                                    name="background" 
                                    accept="image/png,image/jpeg,image/jpg"
                                    class="hidden"
                                    onchange="previewImage(this, 'background-preview')"
                                >
                            </div>
                        </label>
                        <!-- Preview -->
                        <div id="background-preview" class="hidden w-32 h-48 border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-700">
                            <img src="" alt="Background preview" class="w-full h-full object-cover">
                        </div>
                    </div>
                    @error('background')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Set as Default -->
                <div class="flex items-center gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <input 
                        type="checkbox" 
                        id="is_default" 
                        name="is_default" 
                        value="1"
                        {{ old('is_default', $template->is_default) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 bg-white border-gray-300 rounded focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                    >
                    <label for="is_default" class="text-sm text-gray-700 dark:text-gray-300">
                        Deze template instellen als <strong>standaard</strong> voor nieuwe facturen
                    </label>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button 
                        type="submit" 
                        class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Wijzigingen Opslaan
                    </button>
                    <a 
                        href="{{ route('templates.index') }}" 
                        class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600 transition-colors"
                    >
                        Annuleren
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const img = preview.querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</x-app-layout>
