<x-app-layout>
    <div x-data="templateEditor()" x-init="init()">

        {{-- Mobiele waarschuwing --}}
        <div x-data="{ showMobileWarning: window.innerWidth < 1024 }"
             x-show="showMobileWarning"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 p-4"
             style="display: none;">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6 text-center"
                 @click.away="showMobileWarning = false">
                <div class="text-5xl mb-4">💻</div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                    Niet optimaal op mobiel
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-6">
                    De template editor werkt het beste op een laptop of desktop.
                    Op een klein scherm kun je velden niet goed slepen en positioneren.
                    We raden aan om templates te bewerken op een groter scherm.
                </p>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('templates.index') }}"
                       class="inline-flex justify-center items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        ← Terug naar templates
                    </a>
                    <button @click="showMobileWarning = false"
                            class="inline-flex justify-center items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors">
                        Toch doorgaan
                    </button>
                </div>
            </div>
        </div>

        <div class="py-6">
            <div class="max-w-full mx-auto px-4">

                {{-- Toolbar --}}
                <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
                    <h2 class="font-semibold text-xl text-gray-900 dark:text-white leading-tight">
                        Template Editor: {{ $template->name }}
                    </h2>
                    <div class="flex gap-2">
                        <button @click="clearAll"
                                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            🗑️ Alles Wissen
                        </button>
                        <div class="relative" x-data="{ styleMenuOpen: false }">
                            <button @click="styleMenuOpen = !styleMenuOpen"
                                    class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                🎨 Stijl toepassen ▾
                            </button>
                            <div x-show="styleMenuOpen"
                                 @click.away="styleMenuOpen = false"
                                 x-transition
                                 class="absolute right-0 mt-2 w-72 bg-white border border-gray-200 rounded-lg shadow-xl z-50 overflow-hidden"
                                 style="display: none;">
                                <template x-for="[presetKey, preset] in Object.entries(presets)" :key="presetKey">
                                    <button @click="if (confirm('Stijl \'' + preset.name + '\' toepassen? De huidige veldposities worden vervangen.')) { applyPreset(presetKey); styleMenuOpen = false; }"
                                            class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                        <span class="flex gap-1 mt-1 shrink-0">
                                            <template x-for="color in preset.colors" :key="color">
                                                <span class="inline-block w-3 h-3 rounded-full border border-gray-300" :style="`background-color: ${color};`"></span>
                                            </template>
                                        </span>
                                        <span>
                                            <span class="block text-sm font-semibold text-gray-900" x-text="preset.name"></span>
                                            <span class="block text-xs text-gray-500" x-text="preset.description"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <a href="{{ route('templates.index') }}"
                           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            ← Terug
                        </a>
                    </div>
                </div>

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

                        {{-- Logo Upload --}}
                        <div class="bg-white border border-gray-300 rounded-lg p-4">
                            <h3 class="font-bold text-gray-900 mb-3">🖼️ Logo</h3>

                            {{-- Huidig logo preview --}}
                            <div x-show="logoUrl" class="mb-3">
                                <img :src="logoUrl" class="h-16 w-auto max-w-full object-contain border border-gray-200 rounded bg-gray-50 p-1">
                                <div class="flex items-center gap-2 mt-2">
                                    <button @click="logoPosition = logoPosition || { x: 50, y: 50, width: 150, height: 80 }; $nextTick(() => setupDragAndDrop())"
                                            x-show="!logoPosition"
                                            class="text-xs bg-orange-100 text-orange-700 border border-orange-300 rounded px-2 py-1 hover:bg-orange-200">
                                        + Plaatsen op canvas
                                    </button>
                                    <span x-show="logoPosition" class="text-xs text-green-600 font-medium">✓ Op canvas</span>
                                    <button @click="logoUrl = null; logoPosition = null"
                                            class="ml-auto text-xs text-red-500 hover:text-red-700">✕ Verwijderen</button>
                                </div>
                            </div>

                            {{-- Upload zone --}}
                            <div class="border-2 border-dashed rounded-lg p-3 text-center cursor-pointer hover:border-orange-400 transition"
                                 :class="logoUploading ? 'border-orange-300 bg-orange-50' : 'border-gray-300'"
                                 @click="$refs.logoInput.click()">
                                <svg class="mx-auto h-8 w-8 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <p class="text-xs text-gray-500" x-text="logoUploading ? 'Uploading...' : (logoUrl ? 'Ander logo uploaden' : 'Klik om logo te uploaden')"></p>
                                <p class="text-xs text-gray-400">PNG, JPG tot 5MB</p>
                            </div>
                            <input type="file" x-ref="logoInput" accept="image/png,image/jpeg,image/jpg" class="hidden"
                                   x-on:change="uploadLogo($event)">
                            <p x-show="logoUploadError" x-text="logoUploadError" class="mt-1 text-xs text-red-600"></p>
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

                            {{-- Vrije tekstvelden --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Vrije tekst</h4>
                                <div class="space-y-2">
                                    <input type="text" x-model="newTextLabel"
                                           placeholder="Bijv: Totaal excl. BTW"
                                           @keydown.enter="addTextBlock()"
                                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-pink-400 focus:outline-none">
                                    <button @click="addTextBlock()"
                                            :disabled="!newTextLabel.trim()"
                                            class="w-full bg-pink-100 border border-pink-300 text-pink-800 rounded px-3 py-2 hover:bg-pink-200 transition text-sm font-medium disabled:opacity-40 disabled:cursor-not-allowed">
                                        + Tekstveld toevoegen
                                    </button>
                                </div>
                            </div>

                            {{-- Decoratie: kleurvlakken --}}
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Decoratie</h4>
                                <button @click="addColorRect()"
                                        class="w-full bg-indigo-100 border border-indigo-300 text-indigo-800 rounded px-3 py-2 hover:bg-indigo-200 transition text-sm font-medium">
                                    🟦 Kleurvlak toevoegen
                                </button>
                                <p class="text-xs text-gray-400 mt-1">
                                    Sleep en vergroot het vlak op het canvas; de kleur pas je aan via het potlood.
                                </p>
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
                                     :style="canvasBackgroundStyle()"
                                     @click="deselectField()">

                                    {{-- Logo Preview (draggable + resizable) --}}
                                    <template x-if="logoUrl && logoPosition">
                                        <div class="absolute logo-draggable border-2 cursor-move transition group"
                                             :class="{
                                                'border-solid border-blue-600 bg-blue-50 bg-opacity-40 ring-2 ring-blue-400 ring-offset-1': selectedField === 'logo',
                                                'border-dashed border-orange-500 bg-orange-50 bg-opacity-30 hover:bg-orange-100': selectedField !== 'logo'
                                             }"
                                             data-field-key="logo"
                                             @click.stop="selectField('logo')"
                                             :style="`left: ${logoPosition.x}px; top: ${logoPosition.y}px; width: ${logoPosition.width}px; height: ${logoPosition.height}px;`">
                                            <img :src="logoUrl"
                                                 alt="Logo"
                                                 class="w-full h-full object-contain pointer-events-none">
                                            <div class="absolute top-0 right-0 -mt-2 mr-3 opacity-0 group-hover:opacity-100 transition">
                                                <button @click="removeLogo"
                                                        class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 shadow-lg"
                                                        title="Logo verwijderen">
                                                    ✕
                                                </button>
                                            </div>
                                            <span class="absolute bottom-0 left-0 text-xs font-semibold text-orange-700 bg-white bg-opacity-75 px-1">Logo</span>
                                            <template x-if="selectedField === 'logo'">
                                                <div class="absolute pointer-events-none select-none"
                                                     style="bottom:-18px;left:0;font-size:10px;white-space:nowrap;background:rgba(37,99,235,0.85);color:#fff;padding:1px 5px;border-radius:3px;z-index:200;">
                                                    <span x-text="`x: ${logoPosition.x}  y: ${logoPosition.y}  b: ${logoPosition.width}  h: ${logoPosition.height}`"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Placed Fields on Canvas --}}
                                    <template x-for="(field, key) in placedFields" :key="key">
                                        <div class="absolute draggable-placed border-2 cursor-move flex items-center transition group"
                                             :class="{
                                                'justify-start': (field.align || 'left') === 'left',
                                                'justify-center': field.align === 'center',
                                                'justify-end': field.align === 'right',
                                                'border-solid border-blue-600 bg-blue-50 bg-opacity-70 ring-2 ring-blue-400 ring-offset-1': selectedField === key,
                                                'border-dashed border-indigo-500 bg-indigo-50 bg-opacity-60 hover:bg-indigo-100 hover:border-indigo-600': selectedField !== key
                                             }"
                                             :data-field-key="key"
                                             @click.stop="selectField(key)"
                                             :style="`left: ${field.x}px; top: ${field.y}px; width: ${field.width}px; height: ${field.height}px; font-size: ${field.fontSize || 12}px; font-family: ${field.fontFamily || 'inherit'}; text-align: ${field.align || 'left'}; font-weight: ${field.fontWeight || 'normal'}; color: ${field.color || 'inherit'}; ${field.backgroundColor ? 'background-color:' + field.backgroundColor + ';' : ''}`">
                                            {{-- Artikelen tabel: toon voorbeeldtabel --}}
                                            <template x-if="key === 'items_table'">
                                                <div class="w-full h-full overflow-hidden pointer-events-none select-none" :style="`font-size: ${field.fontSize || 10}px; font-family: ${field.fontFamily || 'inherit'}; font-weight: ${field.fontWeight || 'normal'};`">
                                                    <table style="width:100%;border-collapse:collapse;">
                                                        <thead>
                                                            <tr :style="`background:${field.headerBg || '#e5e7eb'};color:${field.headerColor || '#111827'};`">
                                                                <th :style="`text-align:left;padding:2px 4px;${tablePreviewBorder(field, true)}`">Omschrijving</th>
                                                                <th :style="`text-align:right;padding:2px 4px;width:40px;${tablePreviewBorder(field, true)}`">Aantal</th>
                                                                <th :style="`text-align:right;padding:2px 4px;width:60px;${tablePreviewBorder(field, true)}`">Prijs</th>
                                                                <th :style="`text-align:right;padding:2px 4px;width:60px;${tablePreviewBorder(field, true)}`">Totaal</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td :style="`padding:2px 4px;${tablePreviewBorder(field)}`">Webhosting Premium</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">1</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">€ 49,95</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">€ 49,95</td>
                                                            </tr>
                                                            <tr :style="(field.zebra ?? true) ? 'background:#f9fafb;' : ''">
                                                                <td :style="`padding:2px 4px;${tablePreviewBorder(field)}`">SSL Certificaat</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">1</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">€ 29,95</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">€ 29,95</td>
                                                            </tr>
                                                            <tr>
                                                                <td :style="`padding:2px 4px;${tablePreviewBorder(field)}`">Support (5 uur)</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">5</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">€ 75,00</td>
                                                                <td :style="`text-align:right;padding:2px 4px;${tablePreviewBorder(field)}`">€ 375,00</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </template>
                                            {{-- Vrije tekstvelden: toon de ingestelde tekst --}}
                                            <template x-if="key !== 'items_table' && field.staticText !== undefined">
                                                <span class="pointer-events-none select-none px-1 w-full" x-text="field.staticText || field.label"></span>
                                            </template>
                                            {{-- Dynamische velden: toon met {label} --}}
                                            <template x-if="key !== 'items_table' && field.staticText === undefined">
                                                <span class="font-semibold text-gray-800 pointer-events-none select-none truncate px-1 italic" x-text="'{' + field.label + '}'"></span>
                                            </template>

                                            {{-- Actieknoppen: iets naar binnen zodat ze de resize-hoeken vrijlaten --}}
                                            <div class="absolute top-0 right-0 -mt-2 mr-3 opacity-0 group-hover:opacity-100 transition flex gap-1" style="pointer-events: none;" :style="'pointer-events: auto;'">
                                                <button @click.stop="openFieldEditor(key)"
                                                        class="bg-blue-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-blue-600 shadow-lg"
                                                        title="Veld bewerken">✎</button>
                                                <button @click.stop="removeField(key)"
                                                        class="bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 shadow-lg"
                                                        title="Veld verwijderen">✕</button>
                                            </div>

                                            {{-- Positie-readout bij selectie (pijltjestoetsen) --}}
                                            <template x-if="selectedField === key">
                                                <div class="absolute pointer-events-none select-none"
                                                     style="bottom:-18px;left:0;font-size:10px;white-space:nowrap;background:rgba(37,99,235,0.85);color:#fff;padding:1px 5px;border-radius:3px;z-index:200;">
                                                    <span x-text="`x: ${field.x}  y: ${field.y}  b: ${field.width}  h: ${field.height}`"></span>
                                                </div>
                                            </template>

                                            {{-- Resize handles: alleen in de 4 hoeken (8x8px), zodat midden altijd sleepbaar blijft --}}
                                            <div class="resize-tl absolute bg-white border border-indigo-400 rounded-sm opacity-0 group-hover:opacity-100 transition"
                                                 style="width:8px;height:8px;top:-3px;left:-3px;cursor:nw-resize;"></div>
                                            <div class="resize-tr absolute bg-white border border-indigo-400 rounded-sm opacity-0 group-hover:opacity-100 transition"
                                                 style="width:8px;height:8px;top:-3px;right:-3px;cursor:ne-resize;"></div>
                                            <div class="resize-bl absolute bg-white border border-indigo-400 rounded-sm opacity-0 group-hover:opacity-100 transition"
                                                 style="width:8px;height:8px;bottom:-3px;left:-3px;cursor:sw-resize;"></div>
                                            <div class="resize-br absolute bg-white border border-indigo-400 rounded-sm opacity-0 group-hover:opacity-100 transition"
                                                 style="width:8px;height:8px;bottom:-3px;right:-3px;cursor:se-resize;"></div>
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
                                {{-- PDF Test dropdown --}}
                                <div class="relative" x-data="{ open: false }">
                                    <div class="flex">
                                        <a :href="`/templates/${template.id}/test-pdf?rows=short`"
                                           target="_blank"
                                           class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-l-lg shadow-lg text-lg transition">
                                            📄 PDF Testen
                                        </a>
                                        <button @click="open = !open" @click.outside="open = false"
                                                class="bg-green-700 hover:bg-green-800 text-white font-bold py-4 px-3 rounded-r-lg shadow-lg border-l border-green-500 transition">
                                            ▾
                                        </button>
                                    </div>
                                    <div x-show="open" x-cloak
                                         class="absolute bottom-full mb-1 left-0 bg-white border border-gray-200 rounded-lg shadow-xl z-50 w-64 overflow-hidden">
                                        <a :href="`/templates/${template.id}/test-pdf?rows=short`"
                                           target="_blank" @click="open = false"
                                           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition border-b border-gray-100">
                                            <span class="text-xl">📄</span>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">Korte tabel (1 pagina)</p>
                                                <p class="text-xs text-gray-500">3 artikelen, past op 1 pagina</p>
                                            </div>
                                        </a>
                                        <a :href="`/templates/${template.id}/test-pdf?rows=long`"
                                           target="_blank" @click="open = false"
                                           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition">
                                            <span class="text-xl">📋</span>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">Lange tabel (2+ pagina's)</p>
                                                <p class="text-xs text-gray-500">25 artikelen, doorlopend over pagina's</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

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
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4" x-data="{ editorTab: 'instellingen' }">
                <div class="p-6 pb-0">
                    <h3 class="text-xl font-bold mb-4">✎ Veld Bewerken</h3>

                    {{-- Tabs --}}
                    <div class="flex border-b border-gray-200 mb-4">
                        <button @click="editorTab = 'instellingen'"
                                :class="editorTab === 'instellingen' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                                class="px-4 py-2 text-sm transition">
                            Instellingen
                        </button>
                        <button @click="editorTab = 'plaatsing'"
                                :class="editorTab === 'plaatsing' ? 'border-b-2 border-blue-600 text-blue-600 font-semibold' : 'text-gray-500 hover:text-gray-700'"
                                class="px-4 py-2 text-sm transition">
                            Plaatsing
                        </button>
                    </div>
                </div>

                <template x-if="editingField && placedFields[editingField]">
                    <div class="px-6 pb-6">

                        {{-- TAB: Instellingen --}}
                        <div x-show="editorTab === 'instellingen'" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Veld Naam</label>
                                <input type="text"
                                       :value="placedFields[editingField]?.label"
                                       disabled
                                       class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100 text-gray-600">
                            </div>

                            {{-- Tekst inhoud: alleen voor vrije tekstvelden --}}
                            <div x-show="placedFields[editingField]?.staticText !== undefined && !isRect(editingField)">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tekst inhoud</label>
                                <input type="text"
                                       x-model="placedFields[editingField].staticText"
                                       placeholder="Typ hier je tekst..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-pink-400 focus:outline-none">
                            </div>

                            {{-- Tekstkleur --}}
                            <div x-show="editingField !== 'items_table' && !isRect(editingField)">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tekstkleur</label>
                                <div class="flex items-center gap-2">
                                    <input type="color"
                                           :value="placedFields[editingField]?.color || '#111827'"
                                           @input="placedFields[editingField].color = $event.target.value"
                                           class="w-10 h-10 border border-gray-300 rounded cursor-pointer p-0.5">
                                    <span class="text-xs text-gray-500" x-text="placedFields[editingField]?.color || 'standaard (zwart)'"></span>
                                    <button @click="placedFields[editingField].color = ''"
                                            class="ml-auto text-xs bg-gray-200 text-gray-700 rounded px-2 py-1 hover:bg-gray-300">
                                        ↺ Standaard
                                    </button>
                                </div>
                            </div>

                            {{-- Vlak-/achtergrondkleur --}}
                            <div x-show="editingField !== 'items_table'">
                                <label class="block text-sm font-medium text-gray-700 mb-1"
                                       x-text="isRect(editingField) ? 'Vlakkleur' : 'Achtergrondkleur'"></label>
                                <div class="flex items-center gap-2">
                                    <input type="color"
                                           :value="placedFields[editingField]?.backgroundColor || '#ffffff'"
                                           @input="placedFields[editingField].backgroundColor = $event.target.value"
                                           class="w-10 h-10 border border-gray-300 rounded cursor-pointer p-0.5">
                                    <span class="text-xs text-gray-500" x-text="placedFields[editingField]?.backgroundColor || 'transparant'"></span>
                                    <button x-show="!isRect(editingField)"
                                            @click="placedFields[editingField].backgroundColor = ''"
                                            class="ml-auto text-xs bg-gray-200 text-gray-700 rounded px-2 py-1 hover:bg-gray-300">
                                        ↺ Transparant
                                    </button>
                                </div>
                            </div>

                            {{-- Tabelstijl: alleen voor artikelen tabel --}}
                            <template x-if="editingField === 'items_table'">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kopregel achtergrond</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color"
                                                   :value="placedFields[editingField]?.headerBg || '#f0f0f0'"
                                                   @input="placedFields[editingField].headerBg = $event.target.value"
                                                   class="w-10 h-10 border border-gray-300 rounded cursor-pointer p-0.5">
                                            <span class="text-xs text-gray-500" x-text="placedFields[editingField]?.headerBg || '#f0f0f0'"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kopregel tekstkleur</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color"
                                                   :value="placedFields[editingField]?.headerColor || '#000000'"
                                                   @input="placedFields[editingField].headerColor = $event.target.value"
                                                   class="w-10 h-10 border border-gray-300 rounded cursor-pointer p-0.5">
                                            <span class="text-xs text-gray-500" x-text="placedFields[editingField]?.headerColor || '#000000'"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Randen</label>
                                        <div class="grid grid-cols-3 gap-1">
                                            <button @click="placedFields[editingField].borderStyle = 'full'"
                                                    :class="(!placedFields[editingField]?.borderStyle || placedFields[editingField]?.borderStyle === 'full') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                                    class="py-2 px-2 rounded text-xs font-medium hover:bg-blue-500 hover:text-white transition">
                                                ▦ Volledig
                                            </button>
                                            <button @click="placedFields[editingField].borderStyle = 'horizontal'"
                                                    :class="placedFields[editingField]?.borderStyle === 'horizontal' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                                    class="py-2 px-2 rounded text-xs font-medium hover:bg-blue-500 hover:text-white transition">
                                                ☰ Horizontaal
                                            </button>
                                            <button @click="placedFields[editingField].borderStyle = 'minimal'"
                                                    :class="placedFields[editingField]?.borderStyle === 'minimal' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                                    class="py-2 px-2 rounded text-xs font-medium hover:bg-blue-500 hover:text-white transition">
                                                — Minimaal
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Om-en-om rijkleur</label>
                                        <button @click="placedFields[editingField].zebra = !(placedFields[editingField]?.zebra ?? true)"
                                                :class="(placedFields[editingField]?.zebra ?? true) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                                class="py-2 px-4 rounded text-sm font-medium hover:bg-blue-500 hover:text-white transition"
                                                x-text="(placedFields[editingField]?.zebra ?? true) ? '✓ Zebra aan' : 'Zebra uit'">
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Lettertype --}}
                            <div x-show="!isRect(editingField)">
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

                            <div x-show="!isRect(editingField)">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Lettergrootte: <span x-text="placedFields[editingField]?.fontSize || 12"></span>px
                                </label>
                                <input type="range"
                                       x-model.number="placedFields[editingField].fontSize"
                                       min="6"
                                       max="48"
                                       step="1"
                                       class="w-full">
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>6px</span>
                                    <span>48px</span>
                                </div>
                            </div>

                            {{-- Dikgedrukt toggle --}}
                            <div x-show="editingField !== 'items_table' && !isRect(editingField)">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tekststijl</label>
                                <button @click="placedFields[editingField].fontWeight = (placedFields[editingField]?.fontWeight === 'bold') ? 'normal' : 'bold'"
                                        :class="placedFields[editingField]?.fontWeight === 'bold' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                        class="py-2 px-4 rounded font-bold hover:bg-blue-500 hover:text-white transition">
                                    B Dikgedrukt
                                </button>
                            </div>

                            {{-- Uitlijning: verbergen voor artikelen tabel en kleurvlakken --}}
                            <div x-show="editingField !== 'items_table' && !isRect(editingField)">
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

                            {{-- Pagina zichtbaarheid --}}
                            <div x-show="editingField !== 'items_table'">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zichtbaar op pagina</label>
                                <div class="grid grid-cols-3 gap-1">
                                    <button @click="placedFields[editingField].pageVisibility = 'all'"
                                            :class="(!placedFields[editingField]?.pageVisibility || placedFields[editingField]?.pageVisibility === 'all') ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                                            class="py-2 px-2 rounded text-xs font-medium hover:bg-indigo-500 hover:text-white transition">
                                        📄 Alle
                                    </button>
                                    <button @click="placedFields[editingField].pageVisibility = 'first'"
                                            :class="placedFields[editingField]?.pageVisibility === 'first' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                                            class="py-2 px-2 rounded text-xs font-medium hover:bg-indigo-500 hover:text-white transition">
                                        1️⃣ Eerste
                                    </button>
                                    <button @click="placedFields[editingField].pageVisibility = 'last'"
                                            :class="placedFields[editingField]?.pageVisibility === 'last' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                                            class="py-2 px-2 rounded text-xs font-medium hover:bg-indigo-500 hover:text-white transition">
                                        🔚 Laatste
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">
                                    "Alle" = herhaalt op elke pagina bij lange facturen
                                </p>
                            </div>
                        </div>

                        {{-- TAB: Plaatsing --}}
                        <div x-show="editorTab === 'plaatsing'" class="space-y-4">
                            <p class="text-xs text-gray-500">Canvas is 850 × 1200 px (A4 op 100%).</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">X (links)</label>
                                    <input type="number"
                                           x-model.number="placedFields[editingField].x"
                                           @change="placedFields[editingField].x = Math.max(0, Math.min(placedFields[editingField].x, 850 - placedFields[editingField].width))"
                                           min="0" max="850" step="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Y (top)</label>
                                    <input type="number"
                                           x-model.number="placedFields[editingField].y"
                                           @change="placedFields[editingField].y = Math.max(0, Math.min(placedFields[editingField].y, 1200 - placedFields[editingField].height))"
                                           min="0" max="1200" step="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Breedte</label>
                                    <input type="number"
                                           x-model.number="placedFields[editingField].width"
                                           @change="placedFields[editingField].width = Math.max(10, Math.min(placedFields[editingField].width, 850))"
                                           min="10" max="850" step="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hoogte</label>
                                    <input type="number"
                                           x-model.number="placedFields[editingField].height"
                                           @change="placedFields[editingField].height = Math.max(2, Math.min(placedFields[editingField].height, 1200))"
                                           min="2" max="1200" step="1"
                                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                </div>
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
        // Stijlsjablonen — single source of truth in PHP (TemplatePresets)
        const TEMPLATE_PRESETS = @json(\App\Services\TemplatePresets::all());

        document.addEventListener('alpine:init', () => {
            Alpine.data('templateEditor', () => ({
                template: @json($template),
                fields: @json($template->field_positions ?? []),
                presets: TEMPLATE_PRESETS,
                placedFields: {},
                logoPosition: null,
                editingField: null,
                selectedField: null,
                history: [],
                historyIndex: -1,
                _arrowDebounceTimer: null,
                newTextLabel: '',
                logoUploading: false,
                logoUploadError: null,
                logoUrl: '{{ $template->logo_path ? route("templates.serve-file", [$template, "logo"]) : null }}',
                backgroundUrl: '{{ $template->background_path ? route("templates.serve-file", [$template, "background"]) : null }}',

                // Field definitions
                companyFields: [
                    { id: 'company_name', align: 'left', label: 'Bedrijfsnaam' },
                    { id: 'company_address', align: 'left', label: 'Bedrijfsadres' },
                    { id: 'company_postal_code', align: 'left', label: 'Bedrijfs Postcode' },
                    { id: 'company_city', align: 'left', label: 'Bedrijfs Plaats' },
                    { id: 'company_email', align: 'left', label: 'Bedrijfs E-mail' },
                    { id: 'company_phone', align: 'left', label: 'Bedrijfs Telefoon' },
                    { id: 'company_website', align: 'left', label: 'Bedrijfs Website' },
                    { id: 'company_kvk', align: 'left', label: 'KvK Nummer' },
                    { id: 'company_vat', align: 'left', label: 'BTW Nummer' },
                    { id: 'company_iban', align: 'left', label: 'IBAN' },
                    { id: 'company_bic', align: 'left', label: 'BIC' },
                    { id: 'company_bank', align: 'left', label: 'Banknaam' },
                ],
                clientFields: [
                    { id: 'client_name', align: 'left', label: 'Klantnaam' },
                    { id: 'client_address', align: 'left', label: 'Klantadres' },
                    { id: 'client_postal_code', align: 'left', label: 'Klant Postcode' },
                    { id: 'client_city', align: 'left', label: 'Klant Plaats' },
                    { id: 'client_email', align: 'left', label: 'Klant E-mail' },
                ],
                invoiceFields: [
                    { id: 'invoice_number', align: 'left', label: 'Factuurnummer' },
                    { id: 'invoice_date', align: 'left', label: 'Factuurdatum' },
                    { id: 'due_date', align: 'left', label: 'Vervaldatum' },
                    { id: 'invoice_reference', align: 'left', label: 'Referentie' },
                    @if(\App\Models\AppSetting::get()->credit_surcharge_enabled)
                    { id: 'credit_surcharge', align: 'right', label: 'Kredietbeperking' },
                    { id: 'total_with_surcharge', align: 'right', label: 'Totaal incl. kredietbeperking' },
                    @endif
                ],
                specialFields: [
                    { id: 'items_table', align: 'left', label: 'Artikelen Tabel' },
                    { id: 'subtotal', align: 'left', label: 'Subtotaal' },
                    { id: 'tax', align: 'left', label: 'BTW' },
                    { id: 'total', align: 'left', label: 'Totaal' },
                    { id: 'payment_terms', align: 'left', label: 'Betalingsvoorwaarden' },
                    { id: 'notes', align: 'left', label: 'Opmerkingen' },
                ],

                init() {
                    this.initializePlacedFields();
                    this.$nextTick(() => {
                        this.setupDragAndDrop();
                        this.setupCanvasKeyboard();
                        this.pushHistory(); // baseline snapshot
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
                                    label: position.label || this.getFieldLabel(key)
                                };
                            }
                        }
                    } else {
                        // Load default layout if template is empty
                        this.loadDefaultLayout();
                        // Auto-save default posities naar DB zodat PDF generator
                        // ze ook ziet zonder handmatig opslaan
                        this.$nextTick(() => {
                            this.autoSaveDefaultPositions();
                        });
                    }

                    // Set default logo position if logo exists but no position set
                    if (this.logoUrl && !this.logoPosition) {
                        this.logoPosition = { x: 600, y: 50, width: 150, height: 80 };
                    }

                    console.log('Initialized placedFields:', this.placedFields);
                    console.log('Logo position:', this.logoPosition);
                },

                loadDefaultLayout() {
                    this.applyPreset('klassiek');
                },

                /**
                 * Past een stijlsjabloon uit TEMPLATE_PRESETS toe (single source
                 * of truth: App\Services\TemplatePresets in PHP).
                 */
                applyPreset(key) {
                    const preset = TEMPLATE_PRESETS[key];
                    if (!preset) return;

                    this.pushHistory();
                    const positions = JSON.parse(JSON.stringify(preset.positions));
                    const placed = {};
                    for (const [fieldKey, position] of Object.entries(positions)) {
                        placed[fieldKey] = {
                            ...position,
                            label: position.label || this.getFieldLabel(fieldKey)
                        };
                    }
                    this.placedFields = placed;
                    this.$nextTick(() => this.setupDragAndDrop());
                },

                getFieldLabel(fieldId) {
                    const allFields = [...this.companyFields, ...this.clientFields, ...this.invoiceFields, ...this.specialFields];
                    const field = allFields.find(f => f.id === fieldId);
                    return field ? field.label : fieldId;
                },

                pushHistory() {
                    const snapshot = {
                        placedFields: JSON.parse(JSON.stringify(this.placedFields)),
                        logoPosition: this.logoPosition ? JSON.parse(JSON.stringify(this.logoPosition)) : null,
                    };
                    // Drop any redo-future when a new action is taken
                    this.history = this.history.slice(0, this.historyIndex + 1);
                    this.history.push(snapshot);
                    if (this.history.length > 50) this.history.shift();
                    this.historyIndex = this.history.length - 1;
                },

                undo() {
                    if (this.historyIndex <= 0) return;
                    this.historyIndex--;
                    const snapshot = this.history[this.historyIndex];
                    this.placedFields = JSON.parse(JSON.stringify(snapshot.placedFields));
                    this.logoPosition = snapshot.logoPosition ? JSON.parse(JSON.stringify(snapshot.logoPosition)) : null;
                    this.$nextTick(() => this.setupDragAndDrop());
                },

                selectField(key) {
                    this.selectedField = key;
                },

                deselectField() {
                    this.selectedField = null;
                },

                moveSelectedField(dx, dy) {
                    const key = this.selectedField;
                    if (!key) return;

                    // Push history before the first keypress in a burst, then debounce
                    if (!this._arrowDebounceTimer) {
                        this.pushHistory();
                    }
                    clearTimeout(this._arrowDebounceTimer);
                    this._arrowDebounceTimer = setTimeout(() => {
                        this._arrowDebounceTimer = null;
                        this.pushHistory();
                    }, 600);

                    if (key === 'logo' && this.logoPosition) {
                        this.logoPosition.x = Math.max(0, Math.min(this.logoPosition.x + dx, 850 - this.logoPosition.width));
                        this.logoPosition.y = Math.max(0, Math.min(this.logoPosition.y + dy, 1200 - this.logoPosition.height));
                    } else if (this.placedFields[key]) {
                        this.placedFields[key].x = Math.max(0, Math.min(this.placedFields[key].x + dx, 850 - this.placedFields[key].width));
                        this.placedFields[key].y = Math.max(0, Math.min(this.placedFields[key].y + dy, 1200 - this.placedFields[key].height));
                    }
                },

                setupCanvasKeyboard() {
                    const canvasEl = document.getElementById('canvas');
                    if (!canvasEl) return;

                    // Make the canvas wrapper focusable so it can receive keyboard events
                    const wrapper = canvasEl.closest('[tabindex]') || canvasEl.parentElement.parentElement;
                    const self = this;

                    document.addEventListener('keydown', function(e) {
                        const inInput = document.activeElement && (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA' || document.activeElement.tagName === 'SELECT');

                        // Ctrl+Z / Cmd+Z — undo (global, works even without a selection)
                        if ((e.ctrlKey || e.metaKey) && e.key === 'z' && !e.shiftKey) {
                            if (!inInput) {
                                e.preventDefault();
                                self.undo();
                                return;
                            }
                        }

                        if (!self.selectedField) return;

                        if (e.key === 'Escape') {
                            self.deselectField();
                            return;
                        }

                        if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) === -1) return;
                        if (inInput) return;

                        e.preventDefault();
                        const step = e.shiftKey ? 10 : 1;
                        if (e.key === 'ArrowUp')    self.moveSelectedField(0, -step);
                        if (e.key === 'ArrowDown')  self.moveSelectedField(0,  step);
                        if (e.key === 'ArrowLeft')  self.moveSelectedField(-step, 0);
                        if (e.key === 'ArrowRight') self.moveSelectedField( step, 0);
                    });
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
                                    self.pushHistory();
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
                                },
                                end() { self.pushHistory(); }
                            }
                        });

                    // Make placed fields draggable AND resizable on canvas
                    // Resize alleen via hoek-handles zodat midden altijd sleepbaar blijft
                    interact('.draggable-placed')
                        .draggable({
                            inertia: false,
                            // Ignoreer clicks op de resize-handles en actieknoppen
                            ignoreFrom: '.resize-tl, .resize-tr, .resize-bl, .resize-br, button',
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
                                    self.pushHistory();
                                }
                            }
                        })
                        .resizable({
                            // Alleen de 4 hoek-divs triggeren resize
                            edges: {
                                top:    '.resize-tl, .resize-tr',
                                bottom: '.resize-bl, .resize-br',
                                left:   '.resize-tl, .resize-bl',
                                right:  '.resize-tr, .resize-br',
                            },
                            modifiers: [
                                // Niet buiten het canvas resizen
                                interact.modifiers.restrictEdges({ outer: 'parent' }),
                                interact.modifiers.restrictSize({
                                    // Max = volledige canvasgrootte (850×1200), zodat
                                    // full-width/full-height kleurvlakken mogelijk zijn.
                                    // Min laag genoeg voor dunne lijnen.
                                    min: { width: 10, height: 2 },
                                    max: { width: 850, height: 1200 }
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
                                },
                                end() { self.pushHistory(); }
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
                    this.pushHistory();
                    this.editingField = fieldKey;
                    this.selectedField = null;
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

                    this.pushHistory();
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

                async uploadLogo(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.logoUploading = true;
                    this.logoUploadError = null;

                    const formData = new FormData();
                    formData.append('logo', file);
                    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                    try {
                        const response = await fetch(`/templates/${this.template.id}/upload-logo`, {
                            method: 'POST',
                            body: formData,
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });

                        const text = await response.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch(e) {
                            this.logoUploadError = 'Server fout: ' + text.slice(0, 100);
                            return;
                        }

                        if (!response.ok) {
                            this.logoUploadError = data.message || `Fout ${response.status}`;
                            return;
                        }

                        if (data.success) {
                            this.logoUrl = data.url + '?t=' + Date.now();
                            if (!this.logoPosition) {
                                this.logoPosition = { x: 50, y: 50, width: 150, height: 80 };
                            }
                            this.$nextTick(() => { this.setupDragAndDrop(); });
                        } else {
                            this.logoUploadError = data.message || 'Upload mislukt.';
                        }
                    } catch (e) {
                        this.logoUploadError = 'Upload mislukt: ' + e.message;
                    } finally {
                        this.logoUploading = false;
                        event.target.value = '';
                    }
                },

                addTextBlock() {
                    const text = this.newTextLabel.trim();
                    if (!text) return;

                    this.pushHistory();

                    // Unieke key zodat meerdere tekstvelden mogelijk zijn
                    const key = 'static_text_' + Date.now();
                    const canvasWidth = 850, canvasHeight = 1200;
                    const fieldWidth = 200, fieldHeight = 30;
                    const x = Math.round(Math.random() * (canvasWidth - fieldWidth - 100) + 50);
                    const y = Math.round(Math.random() * (canvasHeight - fieldHeight - 100) + 50);

                    this.placedFields[key] = {
                        x, y,
                        width: fieldWidth,
                        height: fieldHeight,
                        fontSize: 12,
                        fontFamily: 'inherit',
                        align: 'left',
                        label: text,
                        staticText: text, // Markeert dit als vrij tekstveld
                    };

                    this.placedFields = { ...this.placedFields };
                    this.newTextLabel = '';

                    this.$nextTick(() => { this.setupDragAndDrop(); });
                },

                addColorRect() {
                    this.pushHistory();
                    // Unieke key zodat meerdere kleurvlakken mogelijk zijn
                    const key = 'static_rect_' + Date.now();

                    this.placedFields[key] = {
                        x: 60,
                        y: 60,
                        width: 250,
                        height: 60,
                        fontSize: 12,
                        fontFamily: 'inherit',
                        align: 'left',
                        staticText: ' ',           // geen tekst, alleen kleur
                        backgroundColor: '#1e3a8a',
                        label: 'Kleurvlak',
                    };

                    this.placedFields = { ...this.placedFields };
                    this.$nextTick(() => { this.setupDragAndDrop(); });
                },

                isRect(fieldKey) {
                    return typeof fieldKey === 'string' && fieldKey.startsWith('static_rect_');
                },

                /**
                 * Randen-CSS voor de voorbeeldtabel op het canvas,
                 * afgestemd op de gekozen borderStyle (zoals de PDF rendert).
                 */
                tablePreviewBorder(field, isHeader = false) {
                    const color = field.borderColor || '#d1d5db';
                    switch (field.borderStyle) {
                        case 'horizontal':
                            return `border:0;border-bottom:1px solid ${color};`;
                        case 'minimal':
                            return isHeader
                                ? `border:0;border-bottom:2px solid ${color};`
                                : 'border:0;border-bottom:1px solid #e5e7eb;';
                        default:
                            return `border:1px solid ${color};`;
                    }
                },

                removeField(fieldKey) {
                    if (confirm(`Veld "${this.placedFields[fieldKey].label}" verwijderen?`)) {
                        this.pushHistory();
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
                        this.pushHistory();
                        this.logoPosition = null;
                        console.log('Logo removed from canvas');
                    }
                },

                /**
                 * Auto-save default posities naar de database (zonder alert).
                 * Wordt aangeroepen wanneer loadDefaultLayout() een lege template vult.
                 */
                autoSaveDefaultPositions() {
                    const allPositions = { ...this.placedFields };
                    if (this.logoPosition) {
                        allPositions.logo = this.logoPosition;
                    }

                    fetch(`/templates/${this.template.id}/positions`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ field_positions: allPositions })
                    })
                    .then(r => {
                        if (r.ok) console.log('Default posities automatisch opgeslagen');
                        else console.warn('Auto-save mislukt:', r.status);
                    })
                    .catch(e => console.warn('Auto-save fout:', e));
                },

                clearAll() {
                    if (confirm('Alle velden van canvas verwijderen? Dit leegt de hele template.')) {
                        this.pushHistory();
                        this.placedFields = {};
                        this.logoPosition = null;
                        console.log('Cleared all fields');
                    }
                },

                resetToDefault() {
                    if (confirm('Reset naar standaard indeling? Dit verwijdert alle huidige veldposities.')) {
                        this.applyPreset('klassiek');
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
