@php
    $isTemplate = $isTemplate ?? false;
    $groupIndex = $index;
    $role = $stoneGroup['role'] ?? '';
    $distribution = $stoneGroup['distribution'] ?? '';
    $placement = match (true) {
        $role === 'main' && $distribution === 'single' => 'main',
        $role === 'accent' && $distribution === 'symmetric' => 'symmetric',
        $role === 'pave' && $distribution === 'distributed' => 'pave',
        $role === 'halo' && $distribution === 'distributed' => 'halo',
        $role === 'accent' && $distribution === 'distributed' => 'legacy',
        default => '',
    };
    $hasTechnicalData = collect(['origin', 'certification', 'color', 'clarity', 'cut'])
        ->contains(fn (string $field): bool => filled($stoneGroup[$field] ?? null));
    $isConfigured = filled($stoneGroup['stone_id'] ?? null) && filled($placement);
@endphp

<article data-stone-group @if ($isConfigured) data-stone-configured @endif class="overflow-hidden rounded-xl border border-[#e5e6e0] bg-white dark:border-[#4b4a45] dark:bg-[#292b29]">
    @if (! $isTemplate && $role === 'accent' && $distribution === 'symmetric')
        <input type="hidden" name="side_stones[{{ $groupIndex }}][stones_per_side]" value="{{ $stoneGroup['stones_per_side'] ?? '' }}" />
    @endif
    @if (! $isTemplate && filled($stoneGroup['id'] ?? null))
        <input type="hidden" name="stone_groups[{{ $groupIndex }}][id]" value="{{ $stoneGroup['id'] }}" />
    @endif

    <input type="hidden" name="stone_groups[{{ $groupIndex }}][role]" value="{{ $role }}" data-stone-role />
    <input type="hidden" name="stone_groups[{{ $groupIndex }}][distribution]" value="{{ $distribution }}" data-stone-distribution />

    <header class="flex items-start justify-between gap-4 border-b border-[#eef0ec] px-4 py-4 dark:border-[#4b4a45] sm:px-5">
        <div class="min-w-0">
            <p data-stone-group-label class="text-xs font-semibold uppercase tracking-[.12em] text-[#806441]">Grupo de piedras {{ is_numeric($groupIndex) ? $groupIndex + 1 : '' }}</p>
            <p data-stone-group-summary class="mt-1 truncate text-sm text-[#68736c] dark:text-[#c4cbc5]">Elige cómo están colocadas las piedras.</p>
        </div>
        <div class="flex shrink-0 items-center gap-3">
            <button type="button" data-remove-stone-group class="aurea-focus text-xs font-medium text-[#94594e] hover:text-[#713f37]">Eliminar</button>
            <button type="button" data-stone-group-toggle aria-expanded="{{ $isConfigured ? 'false' : 'true' }}" aria-controls="stone-group-content-{{ $groupIndex }}" class="aurea-focus inline-flex items-center gap-1 text-xs font-medium text-[#59635c] hover:text-aurea-focus dark:text-[#d0d4cf]">
                <span data-stone-group-toggle-label>{{ $isConfigured ? 'Editar' : 'Ocultar' }}</span>
                <span aria-hidden="true">⌃</span>
            </button>
        </div>
    </header>

    <div id="stone-group-content-{{ $groupIndex }}" data-stone-group-content class="{{ $isConfigured ? 'hidden' : '' }} space-y-6 p-4 sm:p-5">
        @if ($role === 'other')
            <p class="rounded-lg border border-[#eadfcf] bg-[#fffaf2] px-3 py-2 text-xs text-[#80602f] dark:border-[#66543c] dark:bg-[#3b352c] dark:text-[#ead7b7]">Este grupo usa una configuración personalizada antigua. Sus datos se conservarán mientras no elijas una nueva composición.</p>
        @endif
        @if ($placement === 'legacy')
            <p class="rounded-lg border border-[#eadfcf] bg-[#fffaf2] px-3 py-2 text-xs text-[#80602f] dark:border-[#66543c] dark:bg-[#3b352c] dark:text-[#ead7b7]">Este grupo usa la configuración antigua de acentos. Sus datos se conservarán mientras no elijas una nueva composición.</p>
        @endif

        <section data-stone-composition-section>
            <div class="mb-3">
                <h4 class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">¿Cómo están colocadas las piedras?</h4>
                <p class="mt-1 text-xs text-[#8a938d]">Elige una opción para continuar.</p>
            </div>
            <fieldset data-stone-placement-fieldset>
                <legend class="sr-only">Tipo de composición</legend>
                <div data-stone-placement-options class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'main' => ['Piedra principal', 'Una piedra protagonista de la pieza.'],
                        'symmetric' => ['Laterales simétricas', 'Las mismas piedras se repiten en ambos lados.'],
                        'pave' => ['Pavé', 'Varias piedras distribuidas sobre la superficie.'],
                        'halo' => ['Halo', 'Piedras colocadas alrededor de una piedra principal.'],
                    ] as $value => [$label, $description])
                        <label class="group relative cursor-pointer">
                            <input type="radio" name="stone-placement[{{ $groupIndex }}]" value="{{ $value }}" class="sr-only peer" data-stone-placement @checked($placement === $value) />
                            <span class="flex min-h-24 flex-col rounded-xl border border-[#e5e6e0] bg-[#fdfdfb] p-3 transition group-hover:border-[#c9b28e] peer-checked:border-aurea-gold peer-checked:bg-[#fbf8f3] peer-focus-visible:ring-2 peer-focus-visible:ring-aurea-gold/30 dark:border-[#4b4a45] dark:bg-[#292b29] dark:peer-checked:bg-[#35312a]">
                                <span class="flex items-start gap-2"><span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border border-[#c9cdc7] text-[9px] text-aurea-gold" aria-hidden="true">✓</span><span class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">{{ $label }}</span></span>
                                <span class="mt-2 pl-6 text-[11px] leading-4 text-[#8a938d]">{{ $description }}</span>
                                <span class="relative mt-auto block h-9 pt-2 pl-6 text-[#b58d55]" aria-hidden="true">
                                    @if ($value === 'main')
                                        <span class="absolute left-1/2 top-3 h-4 w-4 -translate-x-1/2 rounded-full bg-[#b58d55] shadow-[0_0_0_4px_rgba(181,141,85,0.15)]"></span>
                                    @elseif ($value === 'symmetric')
                                        <span class="absolute left-1/4 top-4 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute left-[38%] top-4 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute left-1/2 top-2 h-4 w-4 -translate-x-1/2 rounded-full bg-[#b58d55]"></span><span class="absolute right-[38%] top-4 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute right-1/4 top-4 h-2 w-2 rounded-full bg-[#b58d55]"></span>
                                    @elseif ($value === 'pave')
                                        <span class="grid max-w-[112px] grid-cols-6 gap-1.5 pt-1"><span class="h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="h-2 w-2 rounded-full bg-[#b58d55]"></span></span>
                                    @elseif ($value === 'halo')
                                        <span class="absolute left-1/2 top-3 h-4 w-4 -translate-x-1/2 rounded-full bg-[#b58d55]"></span><span class="absolute left-[34%] top-1 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute left-[45%] top-0 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute right-[34%] top-1 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute right-[45%] top-0 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute left-[31%] top-6 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute right-[31%] top-6 h-2 w-2 rounded-full bg-[#b58d55]"></span>
                                    @else
                                        <span class="absolute left-[28%] top-3 h-2 w-2 rounded-full bg-[#b58d55]"></span><span class="absolute left-1/2 top-1 h-4 w-4 -translate-x-1/2 rounded-full bg-[#b58d55]"></span><span class="absolute right-[28%] top-3 h-2 w-2 rounded-full bg-[#b58d55]"></span>
                                    @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div data-stone-placement-summary class="hidden items-center justify-between gap-4 rounded-xl border border-[#eadfcf] bg-[#fffaf2] p-3 dark:border-[#66543c] dark:bg-[#3b352c]">
                <div class="flex min-w-0 items-center gap-3">
                    <span data-stone-placement-summary-diagram class="flex h-12 w-24 shrink-0 items-center justify-center whitespace-pre rounded-lg border border-[#eadfcf] bg-white text-center font-mono text-sm leading-4 tracking-[.16em] text-[#b58d55] dark:border-[#66543c] dark:bg-[#292b29]" aria-hidden="true"></span>
                    <div class="min-w-0"><p data-stone-placement-summary-title class="text-sm font-semibold uppercase tracking-[.08em] text-[#3d4640] dark:text-[#eeeae2]"></p><p data-stone-placement-summary-description class="mt-1 text-xs text-[#8a938d]"></p></div>
                </div>
                <button type="button" data-stone-change-placement class="aurea-focus shrink-0 rounded-lg border border-[#d8d9d2] px-3 py-2 text-xs font-medium text-[#59635c] hover:border-aurea-gold hover:text-[#806441]">Cambiar</button>
            </div>
        </section>

        <section data-stone-stone-section class="hidden border-t border-[#eef0ec] pt-5 dark:border-[#4b4a45]">
            <div class="mb-3"><h4 class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">¿Qué piedra es?</h4><p class="mt-1 text-xs text-[#8a938d]">Define la piedra y sus características principales.</p></div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <label><span class="admin-form-label">Tipo de piedra</span><select name="stone_groups[{{ $groupIndex }}][stone_id]" class="admin-form-input" required><option value="">Selecciona una piedra</option>@foreach ($stones as $stone)<option value="{{ $stone->id }}" @selected((string) ($stoneGroup['stone_id'] ?? '') === (string) $stone->id)>{{ $stone->name }}</option>@endforeach</select>@error("stone_groups.{$groupIndex}.stone_id") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</label>
                <label><span class="admin-form-label">¿Qué forma tiene?</span><input type="text" name="stone_groups[{{ $groupIndex }}][shape]" value="{{ $stoneGroup['shape'] ?? '' }}" placeholder="Ej. Redondo" class="admin-form-input" /></label>
                <label><span class="admin-form-label">¿Cuántos quilates tiene cada piedra?</span><input type="number" name="stone_groups[{{ $groupIndex }}][carats]" value="{{ $stoneGroup['carats'] ?? '' }}" min="0" step="0.01" inputmode="decimal" placeholder="Ej. 0.20" class="admin-form-input" /></label>
            </div>
        </section>

        <section data-stone-quantity-section class="hidden border-t border-[#eef0ec] pt-5 dark:border-[#4b4a45]">
            <div class="mb-3"><h4 class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">¿Cuántas piedras hay?</h4><p class="mt-1 text-xs text-[#8a938d]">La cantidad se interpreta según la composición elegida.</p></div>
            <div class="max-w-sm">
                <label data-stone-quantity-field><span class="admin-form-label">Cantidad total</span><input type="number" name="stone_groups[{{ $groupIndex }}][quantity]" value="{{ $stoneGroup['quantity'] ?? '' }}" min="1" max="1000" step="1" inputmode="numeric" placeholder="Ej. 7" class="admin-form-input" data-stone-quantity /><span class="admin-form-help" data-stone-quantity-help>Número total de piedras de este grupo.</span>@error("stone_groups.{$groupIndex}.quantity") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</label>
                <label class="hidden" data-stone-symmetric-field><span class="admin-form-label">¿Cuántas piedras hay en cada lado?</span><input type="number" name="stone_groups[{{ $groupIndex }}][stones_per_side]" value="{{ $stoneGroup['stones_per_side'] ?? '' }}" min="1" max="50" step="1" inputmode="numeric" placeholder="Ej. 1" class="admin-form-input" data-stones-per-side /><span class="admin-form-help">Se repetirá la misma cantidad en ambos lados.</span><p class="mt-1 text-xs text-[#806441]" data-stone-total>Total en ambos lados: — piedras</p>@error("stone_groups.{$groupIndex}.stones_per_side") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</label>
                <div class="hidden rounded-lg bg-[#fbfbf9] px-3 py-2 text-sm text-[#68736c] dark:bg-[#292b29] dark:text-[#c4cbc5]" data-stone-main-quantity><strong class="font-medium">1 piedra</strong></div>
            </div>
        </section>

        <section data-stone-technical-section class="hidden rounded-xl border border-[#eef0ec] dark:border-[#4b4a45]">
            <div class="p-4"><h4 class="text-sm font-medium text-[#3d4640] dark:text-[#eeeae2]">¿Quieres agregar información técnica de estas piedras?</h4><p class="mt-1 text-xs text-[#8a938d]">Completa solo los datos que apliquen.</p><div class="mt-3 flex flex-wrap gap-3"><label class="flex cursor-pointer items-center gap-2 text-sm text-[#59635c] dark:text-[#d0d4cf]"><input type="radio" name="stone-technical-choice[{{ $groupIndex }}]" value="yes" data-stone-technical-choice class="text-aurea-gold focus:ring-aurea-gold" @checked($hasTechnicalData) />Sí, agregar detalles</label><label class="flex cursor-pointer items-center gap-2 text-sm text-[#59635c] dark:text-[#d0d4cf]"><input type="radio" name="stone-technical-choice[{{ $groupIndex }}]" value="no" data-stone-technical-choice class="text-aurea-gold focus:ring-aurea-gold" @checked(! $hasTechnicalData) />No por ahora</label></div></div>
            <div data-stone-technical-panel class="{{ $hasTechnicalData ? '' : 'hidden' }} grid gap-5 border-t border-[#eef0ec] p-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-[#4b4a45]">@foreach (['origin' => 'Origen', 'certification' => 'Certificación', 'color' => 'Color', 'clarity' => 'Claridad', 'cut' => 'Corte'] as $field => $label)<label><span class="admin-form-label">{{ $label }}</span><input type="text" name="stone_groups[{{ $groupIndex }}][{{ $field }}]" value="{{ $stoneGroup[$field] ?? '' }}" class="admin-form-input" /></label>@endforeach</div>
        </section>
    </div>
</article>
