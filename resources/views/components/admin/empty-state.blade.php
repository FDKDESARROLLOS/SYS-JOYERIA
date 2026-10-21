@props([
    'title',
    'description',
    'actionUrl' => null,
    'actionLabel' => null,
    'actionType' => 'link',
    'actionId' => null,
])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl border border-aurea-border bg-aurea-paper text-aurea-muted dark:bg-[#343633]">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
            <rect x="4" y="4" width="16" height="16" rx="2" />
            <path d="M8 9h8M8 13h5M8 17h3" />
        </svg>
    </span>
    <h4 class="mt-4 text-sm font-semibold text-[#343d37] dark:text-[#eeeae2]">{{ $title }}</h4>
    <p class="mt-1 max-w-sm text-xs leading-5 text-aurea-muted">{{ $description }}</p>
    @if ($actionLabel && ($actionUrl || $actionType === 'button'))
        @if ($actionType === 'button')
            <button
                type="button"
                @if ($actionId) id="{{ $actionId }}" @endif
                data-clear-filters
                class="aurea-focus mt-5 inline-flex cursor-pointer items-center rounded-lg border border-aurea-border px-3 py-2 text-xs font-medium text-aurea-focus hover:border-aurea-gold hover:bg-aurea-paper"
            >
                {{ $actionLabel }}
            </button>
        @else
        <a href="{{ $actionUrl }}" class="aurea-focus mt-5 inline-flex items-center rounded-lg bg-aurea-gold px-3 py-2 text-xs font-medium text-white hover:bg-[#966f3f]">
            {{ $actionLabel }}
        </a>
        @endif
    @endif
</div>
