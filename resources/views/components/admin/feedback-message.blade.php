@props([
    'message',
    'type' => 'success',
])

@php
    $styles = [
        'success' => 'border-[#d8e7dc] bg-[#f4faf5] text-[#4f765a]',
        'error' => 'border-[#efd8d3] bg-[#fdf6f4] text-[#94594e]',
        'info' => 'border-[#dce5eb] bg-[#f5f9fb] text-[#557184]',
    ];
    $icons = [
        'success' => 'M5 12.5 10 17 19 7',
        'error' => 'M7 7l10 10M17 7 7 17',
        'info' => 'M12 7v.01M12 11v6',
    ];
@endphp

<div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm {{ $styles[$type] ?? $styles['info'] }}" role="status">
    <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="{{ $icons[$type] ?? $icons['info'] }}" />
    </svg>
    <span>{{ $message }}</span>
</div>
