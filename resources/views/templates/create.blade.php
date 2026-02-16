<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400 mb-4">
                <a href="{{ route('templates.index') }}" class="hover:text-gray-900 dark:hover:text-gray-200">Templates</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-gray-900 dark:text-white font-medium">Nieuwe Template</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Nieuwe Template Aanmaken ✨</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Upload een logo en achtergrond voor je factuur template.</p>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <form action="{{ route('templates.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf

                <!-- Template Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Template Naam *
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}"
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
                        <option value="A4" {{ old('page_size') === 'A4' ? 'selected' : '' }}>A4 (210 × 297mm)</option>
                        <option value="Letter" {{ old('page_size') === 'Letter' ? 'selected' : '' }}>Letter (8.5 × 11")</option>
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
                    <div class="flex items-center gap-4">
                        <label for="logo" class="flex-1 cursor-pointer">
                            <div class="relative group border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-blue-600 dark:text-blue-400">Klik om te uploaden</span> of sleep een bestand
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
                    <div class="flex items-center gap-4">
                        <label for="background" class="flex-1 cursor-pointer">
                            <div class="relative group border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                                <div class="text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-blue-600 dark:text-blue-400">Klik om te uploaden</span> of sleep een bestand
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
                        {{ old('is_default') ? 'checked' : '' }}
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
                        Template Aanmaken
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
