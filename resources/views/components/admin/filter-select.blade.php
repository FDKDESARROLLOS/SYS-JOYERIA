@props([
    'label',
    'options',
    'id' => null,
    'name' => null,
    'autoSubmit' => false,
])

@php
    $selectId = $id ?? 'filter-' . \Illuminate\Support\Str::slug($label);
    $selectName = $name ?? $selectId;
@endphp

<label for="{{ $selectId }}">

    <span class="mb-2 block text-xs font-medium text-[#68736c]">{{ $label }}
    </span>

    <select
        id="{{ $selectId }}"
        name="{{ $selectName }}"
        @if ($autoSubmit) onchange="this.form?.submit()" @endif
        class="aurea-focus w-full appearance-none rounded-lg border border-[#dfe2dc] bg-aurea-surface px-3 py-2.5 text-sm text-[#56625a] outline-none hover:border-aurea-gold-soft dark:border-[#4b4a45] dark:bg-[#292b29] dark:text-[#f1eee8]">

        <option value="">Todos</option>

        @php
            $optionKeys = array_keys($options);
            $isAssociative = $optionKeys !== range(0, count($optionKeys) - 1);
        @endphp
        @foreach ($options as $optionValue => $optionLabel)
            <option
                value="{{ $isAssociative ? $optionValue : $optionLabel }}"
                @selected((string) request($selectName) === (string) ($isAssociative ? $optionValue : $optionLabel))
            >{{ $isAssociative ? $optionLabel : $optionValue }}</option>
        @endforeach

    </select>
</label>
