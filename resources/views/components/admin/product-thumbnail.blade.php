@props([
    'image' => null,
    'alt' => 'Producto',
    'size' => 'sm',
])

@php
    $hasImage = filled($image) && file_exists(public_path($image));
    $sizeClasses = [
        'xs' => 'h-9 w-9',
        'sm' => 'h-10 w-10',
    ];
@endphp

<span
    class="{{ $sizeClasses[$size] ?? $sizeClasses['sm'] }} flex shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[#e7e2da] bg-[#f7f5f1] text-[#a39a8e] dark:border-[#4b4a45] dark:bg-[#343633] dark:text-[#aeb4ae]"
    aria-hidden="true"
>
    @if ($hasImage)
        <img src="{{ asset($image) }}" alt="{{ $alt }}" class="h-full w-full object-cover" loading="lazy" />
    @else
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3.5" y="4.5" width="17" height="15" rx="2" />
            <circle cx="8.5" cy="9" r="1.25" />
            <path d="m5.5 17 4.5-4 3 2.5 2-2 3.5 3.5" />
        </svg>
    @endif
</span>
