<x-app-layout>
    <div x-data="templateEditor()" x-init="init()">
        <x-slot name="header">
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Template Editor') }}: {{ $template->name }}
                </h2>
                <div class="flex gap-2">
                    <button @click="clearAll" 
                            class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                        🗑️ Alles Wissen
                    </button>
                    <button @click="resetToDefault" 
                            class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                        🔄 Standaard Indeling
                    </button>
                    <a href="{{ route('templates.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        ← Terug
                    </a>
                </div>
            </div>
        </x-slot>

        <div class="py-6">
            <div class="max-w-full mx-auto px-4">
                
                <div class="grid grid-cols-12 gap-6">
                    
                    {{-- LEFT SIDEBAR: Instructions + Available Fields --}}
                    <div class="col-span-3 space-y-6">
                        
                        {{-- Instructions Box --}}
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h3 class="font-bold text-blue-900 mb-3">💡 Instructies</h3>
                            <ul class="text-sm text-blue-800 space-y-2">
                                <li>✅ Klik op een veld om toe te voegen</li>
                                <li>↔ Sleep velden rond op canvas</li>
                                <li>📏 Sleep randen om grootte aan te passen</li>
                                <li>✎ Klik blauw potlood voor font/grootte</li>
                                <li>❌ Klik rode X om te verwijderen</li>
                                <li>💾 Klik "Posities Opslaan" wanneer klaar</li>
                            </ul>
                        </div>

                        {{-- Available Fields --}}
                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                            <h3 class="font-bold text-gray-900 mb-4">📋 Beschikbare Velden</h3>
                            
                            {{-- Company Info Fields --}}
                            <div class="mb-4">
                                <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Bedrijfsgegevens</h4>
                                <div class="space-y-2">
                                    <template x-for="field in companyFields" :key="field.id">
                                        <button @click="addFieldToCanvas(field.id, field.label)"
                                                :disabled="isFieldOnCanvas(field.id)"
                                                class="w-full text-left bg-blue-100 border border-blue-300 rounded px-3 py-2 hover:bg-blue-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="{ 'bg-blue-200': isFieldOnCanvas(field.id) }">
                                            <span x-text="field.label" class="text-sm font-medium"></span>
                                            <span x-show="isFieldOnCanvas(field.id)" class="text-xs text-blue-600 ml-2">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Client Info Fields --}}
                            <div class="mb-4">
                                <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Klantgegevens</h4>
                                <div class="space-y-2">
                                    <template x-for="field in clientFields" :key="field.id">
                                        <button @click="addFieldToCanvas(field.id, field.label)"
                                                :disabled="isFieldOnCanvas(field.id)"
                                                class="w-full text-left bg-green-100 border border-green-300 rounded px-3 py-2 hover:bg-green-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="{ 'bg-green-200': isFieldOnCanvas(field.id) }">
                                            <span x-text="field.label" class="text-sm font-medium"></span>
                                            <span x-show="isFieldOnCanvas(field.id)" class="text-xs text-green-600 ml-2">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Invoice Meta Fields --}}
                            <div class="mb-4">
                                <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Factuur Info</h4>
                                <div class="space-y-2">
                                    <template x-for="field in invoiceFields" :key="field.id">
                                        <button @click="addFieldToCanvas(field.id, field.label)"
                                                :disabled="isFieldOnCanvas(field.id)"
                                                class="w-full text-left bg-purple-100 border border-purple-300 rounded px-3 py-2 hover:bg-purple-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="{ 'bg-purple-200': isFieldOnCanvas(field.id) }">
                                            <span x-text="field.label" class="text-sm font-medium"></span>
                                            <span x-show="isFieldOnCanvas(field.id)" class="text-xs text-purple-600 ml-2">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Special Fields --}}
                            <div>
                                <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Overige</h4>
                                <div class="space-y-2">
                                    <template x-for="field in specialFields" :key="field.id">
                                        <button @click="addFieldToCanvas(field.id, field.label)"
                                                :disabled="isFieldOnCanvas(field.id)"
                                                class="w-full text-left bg-yellow-100 border border-yellow-300 rounded px-3 py-2 hover:bg-yellow-200 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="{ 'bg-yellow-200': isFieldOnCanvas(field.id) }">
                                            <span x-text="field.label" class="text-sm font-medium"></span>
                                            <span x-show="isFieldOnCanvas(field.id)" class="text-xs text-yellow-600 ml-2">✓</span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: A4 Canvas Only --}}
                    <div class="col-span-9">
                        <div class="bg-gray-50 border border-gray-300 rounded-lg p-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">📄 A4 Canvas (100% schaal)</h3>
                            
                            {{-- Canvas Container - Centered --}}
                            <div class="flex justify-center">
                                <div id="canvas" 
                                     class="relative bg-white border-2 border-gray-400 shadow-2xl"
                                     :style="canvasBackgroundStyle()">
                                    
                                    {{-- Logo Preview (draggable + resizable) --}}
                                    <template x-if="logoUrl && logoPosition">
                                        <div class="absolute logo-draggable border-2 border-dashed border-orange-500 bg-orange-50 bg-opacity-30 cursor-move hover:bg-orange-100 transition group"
                                             data-field-key="logo"
                                             :style="`left: ${logoPosition.x}px; top: ${logoPosition.y}px; width: ${logoPosition.width}px; height: ${logoPosition.height}px;`">
                                            <img :src="logoUrl" 
                                                 alt="Logo" 
                                                 class="w-full h-full object-contain pointer-events-none">
                                            <div class="absolute top-0 right-0 -mt-2 -mr-2 opacity-0 group-hover:opacity-100 transition">
                                                <button @click="removeLogo" 
                                                        class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 shadow-lg"
                                                        title="Logo verwijderen">
                                                    ✕
                                                </button>
                                            </div>
                                            <span class="absolute bottom-0 left-0 text-xs font-semibold text-orange-700 bg-white bg-opacity-75 px-1">Logo</span>
                                        </div>
                                    </template>

                                    {{-- Placed Fields on Canvas --}}
                                    <template x-for="(field, key) in placedFields" :key="key">
                                        <div class="absolute draggable-placed border-2 border-dashed border-indigo-500 bg-indigo-50 bg-opacity-60 cursor-move flex items-center hover:bg-indigo-100 hover:border-indigo-600 transition group"
                                             :class="{
                                                'justify-start': (field.align || 'left') === 'left',
                                                'justify-center': field.align === 'center',
                                                'justify-end': field.align === 'right'
                                             }"
                                             :data-field-key="key"
                                             :style="`left: ${field.x}px; top: ${field.y}px; width: ${field.width}px; height: ${field.height}px; font-size: ${field.fontSize || 12}px; font-family: ${field.fontFamily || 'inherit'}; text-align: ${field.align || 'left'};`">
                                            <span class="font-semibold text-gray-800" x-text="field.label"></span>
                                            <div class="absolute top-0 right-0 -mt-2 -mr-2 opacity-0 group-hover:opacity-100 transition flex gap-1">
                                                <button @click.stop="openFieldEditor(key)" 
                                                        class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-blue-600 shadow-lg"
                                                        title="Veld bewerken">
                                                    ✎
                                                </button>
                                                <button @click.stop="removeField(key)" 
                                                        class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 shadow-lg"
                                                        title="Veld verwijderen">
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- Empty State --}}
                                    <template x-if="Object.keys(placedFields).length === 0">
                                        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                                            <div class="text-center text-gray-400">
                                                <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                                </svg>
                                                <p class="text-sm font-medium">Sleep velden van de linkerzijde hierheen</p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="mt-6 flex justify-center gap-4">
                                <a :href="`/templates/${template.id}/test-pdf`" 
                                   target="_blank"
                                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-8 rounded-lg shadow-lg text-lg transition transform hover:scale-105">
                                    📄 PDF Testen
                                </a>
                                <button @click="savePositions" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-8 rounded-lg shadow-lg text-lg transition transform hover:scale-105">
                                    💾 Posities Opslaan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Field Editor Modal --}}
        <div x-show="editingField" 
             x-cloak
             @click.self="closeFieldEditor()"
             class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
             style="display: none;">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-bold mb-4">✎ Veld Bewerken</h3>
                
                <template x-if="editingField && placedFields[editingField]">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veld Naam</label>
                            <input type="text" 
                                   :value="placedFields[editingField]?.label" 
                                   disabled
                                   class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-gray-600">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lettertype</label>
                            <select x-model="placedFields[editingField].fontFamily"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                <option value="inherit">Standaard</option>
                                <option value="Arial, sans-serif">Arial</option>
                                <option value="Helvetica, sans-serif">Helvetica</option>
                                <option value="'Times New Roman', serif">Times New Roman</option>
                                <option value="Georgia, serif">Georgia</option>
                                <option value="'Courier New', monospace">Courier New</option>
                                <option value="Verdana, sans-serif">Verdana</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Lettergrootte: <span x-text="placedFields[editingField]?.fontSize || 12"></span>px
                            </label>
                            <input type="range" 
                                   x-model.number="placedFields[editingField].fontSize"
                                   min="8" 
                                   max="48" 
                                   step="1"
                                   class="w-full">
                            <div class="flex justify-between text-xs text-gray-500">
                                <span>8px</span>
                                <span>48px</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Uitlijning</label>
                            <div class="flex gap-2">
                                <button @click="placedFields[editingField].align = 'left'"
                                        :class="(placedFields[editingField]?.align || 'left') === 'left' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                        class="flex-1 py-2 px-3 rounded font-medium hover:bg-blue-500 hover:text-white transition">
                                    ← Links
                                </button>
                                <button @click="placedFields[editingField].align = 'center'"
                                        :class="placedFields[editingField]?.align === 'center' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                        class="flex-1 py-2 px-3 rounded font-medium hover:bg-blue-500 hover:text-white transition">
                                    ↔ Midden
                                </button>
                                <button @click="placedFields[editingField].align = 'right'"
                                        :class="placedFields[editingField]?.align === 'right' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                        class="flex-1 py-2 px-3 rounded font-medium hover:bg-blue-500 hover:text-white transition">
                                    → Rechts
                                </button>
                            </div>
                        </div>
                        
                        <div class="pt-4 flex gap-2">
                            <button @click="closeFieldEditor()" 
                                    class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                Sluiten
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('templateEditor', () => ({
                template: @json($template),
                fields: @json($template->field_positions ?? []),
                placedFields: {},
                logoPosition: null,
                editingField: null,
                logoUrl: '{{ $template->logo_path ? asset("storage/" . $template->logo_path) : null }}',
                backgroundUrl: '{{ $template->background_path ? asset("storage/" . $template->background_path) : null }}',
                
                // Field definitions
                companyFields: [
                    { id: 'company_name', align: 'left', label: 'Bedrijfsnaam' },
                    { id: 'company_address', align: 'left', label: 'Bedrijfsadres' },
                    { id: 'company_email', align: 'left', label: 'Bedrijfs E-mail' },
                    { id: 'company_phone', align: 'left', label: 'Bedrijfs Telefoon' },
                ],
                clientFields: [
                    { id: 'client_name', align: 'left', label: 'Klantnaam' },
                    { id: 'client_address', align: 'left', label: 'Klantadres' },
                    { id: 'client_email', align: 'left', label: 'Klant E-mail' },
                ],
                invoiceFields: [
                    { id: 'invoice_number', align: 'left', label: 'Factuurnummer' },
                    { id: 'invoice_date', align: 'left', label: 'Factuurdatum' },
                    { id: 'due_date', align: 'left', label: 'Vervaldatum' },
                    { id: 'invoice_reference', align: 'left', label: 'Referentie' },
                ],
                specialFields: [
                    { id: 'items_table', align: 'left', label: 'Artikelen Tabel' },
                    { id: 'subtotal', align: 'left', label: 'Subtotaal' },
                    { id: 'tax', align: 'left', label: 'BTW' },
                    { id: 'total', align: 'left', label: 'Totaal' },
                    { id: 'payment_terms', align: 'left', label: 'Betalingsvoorwaarden' },
                ],

                init() {
                    this.initializePlacedFields();
                    this.$nextTick(() => {
                        this.setupDragAndDrop();
                    });
                },

                initializePlacedFields() {
                    // Load existing field positions from template (skip logo/background)
                    if (this.fields && typeof this.fields === 'object' && Object.keys(this.fields).length > 0) {
                        for (const [key, position] of Object.entries(this.fields)) {
                            if (key === 'logo') {
                                this.logoPosition = { ...position };
                            } else if (key !== 'background') {
                                this.placedFields[key] = {
                                    ...position,
                                    label: this.getFieldLabel(key)
                                };
                            }
                        }
                    } else {
                        // Load default layout if template is empty
                        this.loadDefaultLayout();
                    }
                    
                    // Set default logo position if logo exists but no position set
                    if (this.logoUrl && !this.logoPosition) {
                        this.logoPosition = { x: 600, y: 50, width: 150, height: 80 };
                    }
                    
                    console.log('Initialized placedFields:', this.placedFields);
                    console.log('Logo position:', this.logoPosition);
                },

                loadDefaultLayout() {
                    // Standard professional invoice layout (matching PDF templates)
                    this.placedFields = {
                        'company_name': { x: 50, y: 50, width: 300, height: 40, fontSize: 18, fontFamily: 'inherit', align: 'left', label: 'Bedrijfsnaam' },
                        'company_address': { x: 50, y: 100, width: 300, height: 60, fontSize: 11, fontFamily: 'inherit', align: 'left', label: 'Bedrijfsadres' },
                        'company_email': { x: 50, y: 170, width: 300, height: 20, fontSize: 10, fontFamily: 'inherit', align: 'left', label: 'Bedrijfs E-mail' },
                        'company_phone': { x: 50, y: 195, width: 300, height: 20, fontSize: 10, fontFamily: 'inherit', align: 'left', label: 'Bedrijfs Telefoon' },
                        
                        'invoice_number': { x: 550, y: 150, width: 200, height: 25, fontSize: 12, fontFamily: 'inherit', align: 'left', label: 'Factuurnummer' },
                        'invoice_date': { x: 550, y: 180, width: 200, height: 25, fontSize: 12, fontFamily: 'inherit', align: 'left', label: 'Factuurdatum' },
                        'due_date': { x: 550, y: 210, width: 200, height: 25, fontSize: 12, fontFamily: 'inherit', align: 'left', label: 'Vervaldatum' },
                        
                        'client_name': { x: 50, y: 250, width: 300, height: 30, fontSize: 14, fontFamily: 'inherit', align: 'left', label: 'Klantnaam' },
                        'client_address': { x: 50, y: 290, width: 300, height: 60, fontSize: 11, fontFamily: 'inherit', align: 'left', label: 'Klantadres' },
                        'client_email': { x: 50, y: 360, width: 300, height: 20, fontSize: 10, fontFamily: 'inherit', align: 'left', label: 'Klant E-mail' },
                        
                        'items_table': { x: 50, y: 420, width: 700, height: 300, fontSize: 10, fontFamily: 'inherit', align: 'left', label: 'Artikelen Tabel' },
                        
                        'subtotal': { x: 550, y: 750, width: 200, height: 25, fontSize: 12, fontFamily: 'inherit', align: 'left', label: 'Subtotaal' },
                        'tax': { x: 550, y: 780, width: 200, height: 25, fontSize: 12, fontFamily: 'inherit', align: 'left', label: 'BTW' },
                        'total': { x: 550, y: 810, width: 200, height: 30, fontSize: 16, fontFamily: 'inherit', align: 'left', label: 'Totaal' },
                        
                        'payment_terms': { x: 50, y: 900, width: 700, height: 80, fontSize: 10, fontFamily: 'inherit', align: 'left', label: 'Betalingsvoorwaarden' },
                    };
                },

                getFieldLabel(fieldId) {
                    const allFields = [...this.companyFields, ...this.clientFields, ...this.invoiceFields, ...this.specialFields];
                    const field = allFields.find(f => f.id === fieldId);
                    return field ? field.label : fieldId;
                },

                setupDragAndDrop() {
                    const self = this;
                    const scale = 1.0; // Canvas scale (100%)
                    const interact = window.interact;

                    if (!interact) {
                        console.error('interact.js not loaded!');
                        return;
                    }

                    // Make logo draggable AND resizable
                    // Strategie: update Alpine state LIVE tijdens drag (geen transform flits bij loslaten)
                    interact('.logo-draggable')
                        .draggable({
                            inertia: false,
                            listeners: {
                                start(event) {
                                    event.target.style.zIndex = '100';
                                },
                                move(event) {
                                    if (self.logoPosition) {
                                        self.logoPosition.x = Math.round(self.logoPosition.x + (event.dx / scale));
                                        self.logoPosition.y = Math.round(self.logoPosition.y + (event.dy / scale));
                                        // Clamp binnen canvas
                                        self.logoPosition.x = Math.max(0, Math.min(self.logoPosition.x, 850 - self.logoPosition.width));
                                        self.logoPosition.y = Math.max(0, Math.min(self.logoPosition.y, 1200 - self.logoPosition.height));
                                    }
                                },
                                end(event) {
                                    event.target.style.zIndex = '';
                                }
                            }
                        })
                        .resizable({
                            edges: { left: true, right: true, bottom: true, top: true },
                            modifiers: [
                                interact.modifiers.restrictSize({
                                    min: { width: 50, height: 30 },
                                    max: { width: 400, height: 300 }
                                })
                            ],
                            inertia: false,
                            listeners: {
                                move(event) {
                                    if (self.logoPosition) {
                                        self.logoPosition.x = Math.round(self.logoPosition.x + (event.deltaRect.left / scale));
                                        self.logoPosition.y = Math.round(self.logoPosition.y + (event.deltaRect.top / scale));
                                        self.logoPosition.width = Math.round(event.rect.width / scale);
                                        self.logoPosition.height = Math.round(event.rect.height / scale);
                                    }
                                }
                            }
                        });

                    // Make placed fields draggable AND resizable on canvas
                    // Strategie: update Alpine state LIVE tijdens drag — geen transform, geen flits
                    interact('.draggable-placed')
                        .draggable({
                        inertia: false,
                        listeners: {
                            start(event) {
                                const target = event.target;
                                target.style.zIndex = '100';
                                target.style.opacity = '0.9';
                                target.style.boxShadow = '0 8px 16px rgba(0, 0, 0, 0.2)';
                                target.classList.add('ring-2', 'ring-blue-500');
                            },
                            move(event) {
                                const fieldKey = event.target.dataset.fieldKey;
                                if (self.placedFields[fieldKey]) {
                                    self.placedFields[fieldKey].x = Math.round(self.placedFields[fieldKey].x + (event.dx / scale));
                                    self.placedFields[fieldKey].y = Math.round(self.placedFields[fieldKey].y + (event.dy / scale));
                                    // Clamp binnen canvas
                                    self.placedFields[fieldKey].x = Math.max(0, Math.min(self.placedFields[fieldKey].x, 850 - self.placedFields[fieldKey].width));
                                    self.placedFields[fieldKey].y = Math.max(0, Math.min(self.placedFields[fieldKey].y, 1200 - self.placedFields[fieldKey].height));
                                }
                            },
                            end(event) {
                                const target = event.target;
                                target.style.zIndex = '';
                                target.style.opacity = '';
                                target.style.boxShadow = '';
                                target.classList.remove('ring-2', 'ring-blue-500');
                            }
                        }
                    })
                    .resizable({
                        edges: { left: true, right: true, bottom: true, top: true },
                        modifiers: [
                            interact.modifiers.restrictSize({
                                min: { width: 50, height: 20 },
                                max: { width: 800, height: 400 }
                            })
                        ],
                        inertia: false,
                        listeners: {
                            move(event) {
                                const fieldKey = event.target.dataset.fieldKey;
                                if (self.placedFields[fieldKey]) {
                                    self.placedFields[fieldKey].x = Math.round(self.placedFields[fieldKey].x + (event.deltaRect.left / scale));
                                    self.placedFields[fieldKey].y = Math.round(self.placedFields[fieldKey].y + (event.deltaRect.top / scale));
                                    self.placedFields[fieldKey].width = Math.round(event.rect.width / scale);
                                    self.placedFields[fieldKey].height = Math.round(event.rect.height / scale);
                                }
                            }
                        }
                    });

                    console.log('Drag & Drop initialized');
                },

                canvasBackgroundStyle() {
                    let styles = 'width: 850px; height: 1200px;';
                    if (this.backgroundUrl) {
                        styles += ` background-image: url('${this.backgroundUrl}'); background-size: cover; background-position: center;`;
                    }
                    return styles;
                },

                isFieldOnCanvas(fieldId) {
                    // Use hasOwnProperty for better reactivity
                    return Object.prototype.hasOwnProperty.call(this.placedFields, fieldId);
                },

                openFieldEditor(fieldKey) {
                    this.editingField = fieldKey;
                    console.log('Editing field:', fieldKey);
                },

                closeFieldEditor() {
                    this.editingField = null;
                },

                addFieldToCanvas(fieldId, fieldLabel) {
                    // Don't add if already exists
                    if (this.isFieldOnCanvas(fieldId)) {
                        console.log('Field already on canvas:', fieldId);
                        return;
                    }

                    // Random position within canvas (avoiding edges)
                    const canvasWidth = 850; // A4 width at 100%
                    const canvasHeight = 1200; // A4 height at 100%
                    const fieldWidth = 200;
                    const fieldHeight = 30;

                    const x = Math.round(Math.random() * (canvasWidth - fieldWidth - 100) + 50);
                    const y = Math.round(Math.random() * (canvasHeight - fieldHeight - 100) + 50);

                    // Add to canvas
                    this.placedFields[fieldId] = {
                        x,
                        y,
                        width: fieldWidth,
                        height: fieldHeight,
                        fontSize: 12,
                        fontFamily: 'inherit',
                        align: 'left',
                        label: fieldLabel
                    };

                    // Force reactivity
                    this.placedFields = { ...this.placedFields };
                    
                    console.log(`Added ${fieldLabel} at (${x}, ${y})`);
                    
                    // Re-init draggable after DOM update
                    this.$nextTick(() => {
                        this.setupDragAndDrop();
                    });
                },

                removeField(fieldKey) {
                    if (confirm(`Veld "${this.placedFields[fieldKey].label}" verwijderen?`)) {
                        // Create new object without the field (proper reactivity)
                        const newFields = {};
                        for (const [key, value] of Object.entries(this.placedFields)) {
                            if (key !== fieldKey) {
                                newFields[key] = value;
                            }
                        }
                        this.placedFields = newFields;
                        console.log('Removed field:', fieldKey, 'Remaining fields:', Object.keys(this.placedFields));
                    }
                },

                removeLogo() {
                    if (confirm('Logo van canvas verwijderen? (De upload blijft bewaard)')) {
                        this.logoPosition = null;
                        console.log('Logo removed from canvas');
                    }
                },

                clearAll() {
                    if (confirm('Alle velden van canvas verwijderen? Dit leegt de hele template.')) {
                        this.placedFields = {};
                        this.logoPosition = null;
                        console.log('Cleared all fields');
                    }
                },

                resetToDefault() {
                    if (confirm('Reset naar standaard indeling? Dit verwijdert alle huidige veldposities.')) {
                        this.loadDefaultLayout();
                        this.placedFields = { ...this.placedFields }; // Force reactivity
                        console.log('Reset to default layout');
                    }
                },

                async savePositions() {
                    try {
                        console.log('Saving positions:', this.placedFields);

                        // Merge placed fields with logo/background positions
                        const allPositions = {
                            ...this.placedFields
                        };
                        
                        // Add logo position if exists
                        if (this.logoPosition) {
                            allPositions.logo = this.logoPosition;
                        }
                        
                        // Preserve background if exists
                        if (this.fields && this.fields.background) {
                            allPositions.background = this.fields.background;
                        }

                        const response = await fetch(`/templates/${this.template.id}/positions`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                field_positions: allPositions
                            })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            alert('✅ Posities succesvol opgeslagen!');
                            console.log('Save response:', data);
                        } else {
                            const error = await response.text();
                            alert('❌ Fout bij opslaan posities');
                            console.error('Save error:', error);
                        }
                    } catch (error) {
                        console.error('Save error:', error);
                        alert('❌ Fout bij opslaan: ' + error.message);
                    }
                }
            }));
        });
    </script>
</x-app-layout>
