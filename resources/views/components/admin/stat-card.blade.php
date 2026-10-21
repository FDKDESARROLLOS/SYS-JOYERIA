@props(['title', 'value', 'change', 'description', 'iconBackground', 'iconColor', 'iconPath', 'changeStyle'])

<div class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] dark:bg-[#2d302e]">

    <div class="flex items-start justify-between">

        <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $iconBackground }} {{ $iconColor }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="{{ $iconPath }}" />
            </svg>
        </div>

        {{-- <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ $changeStyle }}">
            {{ $change }}
        </span> --}}
    </div>

    <p class="mt-5 text-sm text-[#7b847e] dark:text-[#aeb4ae]">{{ $title }}</p>

    <p class="mt-1 text-2xl font-semibold tracking-tight text-[#252a27] dark:text-[#f1eee8]">{{ $value }}</p>

    <p class="mt-1 text-[11px] text-[#a0a7a2]">{{ $description }}</p>
</div>
