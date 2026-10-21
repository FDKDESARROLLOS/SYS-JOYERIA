@props([
    'breadcrumbs',
    'title',
    'description',
    'badge' => null,
    'badgeClass' => 'bg-[#e8ddcf] text-[#8f6d43]',
    'required' => false,
])

<div class="mb-8 flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
    <div>
        <div class="mb-2 flex flex-wrap items-center gap-2">
            <nav aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-2 text-sm text-aurea-muted">
                @foreach ($breadcrumbs as $breadcrumbItem)
                    @if (! $loop->last && $breadcrumbItem['url'])
                        <li>
                            <a
                                href="{{ $breadcrumbItem['url'] }}"
                                class="aurea-focus rounded-sm hover:text-aurea-focus"
                            >
                                {{ $breadcrumbItem['label'] }}
                            </a>
                        </li>
                    @else
                        <li aria-current="page" class="text-[#515b55] dark:text-[#eeeae2]">
                            {{ $breadcrumbItem['label'] }}
                        </li>
                    @endif
                    @if (! $loop->last)
                        <li aria-hidden="true" class="text-[#b2b9b4]">/</li>
                    @endif
                @endforeach
            </ol>
            </nav>
            @if ($badge)
                <span class="rounded-full px-2 py-1 text-[10px] font-medium {{ $badgeClass }}">{{ $badge }}</span>
            @endif
        </div>
        <h2 class="font-serif text-3xl text-aurea-ink">{{ $title }}</h2>
        <p class="mt-2 max-w-2xl text-sm text-aurea-muted">{{ $description }}</p>
        @if ($required)
            <p class="mt-3 text-xs text-[#8a938d]"><span class="required-mark">*</span> Campos obligatorios</p>
        @endif
    </div>

    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
