@props([
    'label',
    'description',
    'checked' => false,
    'id' => null,
    'name' => null,
    'disabled' => false,
])

@php
    $toggleId = $id ?? 'toggle-' . \Illuminate\Support\Str::slug($label);
    $descriptionId = $toggleId . '-description';
    $isChecked = filter_var($checked, FILTER_VALIDATE_BOOLEAN);
@endphp

<label for="{{ $toggleId }}" class="flex cursor-pointer items-start justify-between gap-3">
    <span>
        <span class="block text-sm text-[#3d4640] dark:text-[#eeeae2]">{{ $label }}</span>
        <span id="{{ $descriptionId }}" class="mt-0.5 block text-[11px] leading-4 text-[#929b94] dark:text-[#aeb4ae]">{{ $description }}</span>
    </span>
    <span class="relative mt-0.5 inline-flex shrink-0 items-center">
        @if ($name)
            <input type="hidden" name="{{ $name }}" value="0" @disabled($disabled) />
        @endif
        <input
            id="{{ $toggleId }}"
            type="checkbox"
            class="peer sr-only"
            @if ($name) name="{{ $name }}" @endif
            value="1"
            aria-describedby="{{ $descriptionId }}"
            @checked($isChecked)
            @disabled($disabled)
        />
        <span class="admin-toggle-track relative h-5 w-9 rounded-full transition peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-aurea-focus peer-focus-visible:outline-offset-2 after:absolute after:left-1 after:top-1 after:h-3 after:w-3 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4 dark:after:bg-[#f7f4ed]"></span>
    </span>
</label>
