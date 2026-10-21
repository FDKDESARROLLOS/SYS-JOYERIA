@props([
    'product',
    'editUrl' => null,
    'meta' => null,
    'eyebrow' => 'Producto administrado',
])

<section
    class="mb-7 grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-3 rounded-2xl border border-aurea-border bg-aurea-surface p-4 shadow-[0_5px_20px_rgba(35,42,37,0.03)] sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:gap-4 sm:p-5"
    aria-labelledby="product-context-title"
>
    <div class="product-context-thumbnail-frame">
        @if (filled($product['image'] ?? null) && file_exists(public_path($product['image'])))
            <img
                src="{{ asset($product['image']) }}"
                alt="{{ $product['name'] }}"
                class="product-context-thumbnail"
            />
        @else
            <svg class="h-6 w-6 text-[#a39a8e]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
                <circle cx="8.5" cy="9" r="1.25" />
                <path d="m5.5 17 4.5-4 3 2.5 2-2 3.5 3.5" />
            </svg>
        @endif
    </div>
    <div class="min-w-0">
        <p class="text-[10px] font-semibold uppercase tracking-[.18em] text-[#aa8046]">{{ $eyebrow }}</p>
        <h3 id="product-context-title" class="mt-1 font-serif text-2xl text-[#28302b]">{{ $product['name'] }}</h3>
        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-[#929b94]">
            <span>SKU base: <strong class="font-medium text-[#68736c]">{{ $product['base_sku'] }}</strong></span>
            @if (!empty($product['category']))
                <span>{{ $product['category'] }}</span>
            @endif
            @if ($meta)
                <span>{{ $meta }}</span>
            @endif
        </div>
    </div>
    @if ($editUrl)
        <a
            href="{{ $editUrl }}"
            class="aurea-focus col-span-2 w-fit rounded-md border border-[#dfe2dc] px-3 py-2 text-xs font-medium text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus sm:col-span-1 sm:col-start-3 sm:row-span-2 sm:row-start-1 sm:ml-auto"
        >
            Editar producto
        </a>
    @endif
</section>
