@props([
    'label',
    'loadingLabel' => 'Guardando...',
    'type' => 'submit',
])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'aurea-focus inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f] disabled:cursor-not-allowed disabled:border-[#c9c3b9] disabled:bg-[#bdb7ad] disabled:text-white']) }}
    data-loading-button
    data-default-label="{{ $label }}"
    data-loading-label="{{ $loadingLabel }}"
>
    <span data-button-label>{{ $label }}</span>
    <svg data-button-spinner class="hidden h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="8" class="opacity-30" stroke="currentColor" stroke-width="2" />
        <path d="M20 12a8 8 0 0 0-8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
    </svg>
</button>
