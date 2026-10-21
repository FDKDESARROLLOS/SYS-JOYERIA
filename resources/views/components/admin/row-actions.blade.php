@props([
    'label',
    'editUrl' => null,
    'secondaryUrl' => null,
    'secondaryLabel' => null,
    'deleteLabel' => null,
    'deleteUrl' => null,
])

<div class="inline-flex items-center gap-1">
    @if ($editUrl)
        <a href="{{ $editUrl }}"
            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-gold hover:bg-aurea-paper"
            aria-label="Editar {{ $label }}">
            Editar
        </a>
    @endif

    @if ($secondaryUrl && $secondaryLabel)
        <a href="{{ $secondaryUrl }}"
            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-muted hover:bg-aurea-paper hover:text-aurea-focus"
            aria-label="{{ $secondaryLabel }} de {{ $label }}">
            {{ $secondaryLabel }}
        </a>
    @endif
    @if ($deleteLabel && $deleteUrl)
        <button type="button"
            class="delete-record-trigger aurea-focus rounded-md p-2 text-[#6f675d] hover:bg-[#f9e9e6] hover:text-aurea-danger"
            data-delete-trigger data-delete-label="{{ $deleteLabel }}" data-delete-name="{{ $label }}"
            data-delete-url="{{ $deleteUrl }}" aria-label="Eliminar {{ $label }}"
            title="Eliminar {{ $label }}">
            <x-admin.icon name="trash" class="h-[18px] w-[18px]" />
        </button>
    @endif
</div>
