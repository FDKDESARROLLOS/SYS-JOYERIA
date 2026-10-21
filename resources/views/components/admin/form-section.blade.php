@props([
    'number',
    'title',
    'description',
    'id' => null,
])

<section
    class="rounded-2xl border border-aurea-border bg-aurea-surface p-5 shadow-[0_5px_20px_rgba(35,42,37,0.03)] dark:bg-[#2d302e] sm:p-6"
    @if ($id) aria-labelledby="{{ $id }}" @endif
>
    <div class="mb-6 border-b border-[#eef0ec] pb-5">
        <p class="mb-1 text-[10px] font-semibold uppercase tracking-[.18em] text-[#aa8046]">{{ $number }}</p>
        <h3 @if ($id) id="{{ $id }}" @endif class="font-serif text-xl text-[#28302b] dark:text-[#eeeae2]">{{ $title }}</h3>
        <p class="mt-1 text-xs text-[#8a938d]">{{ $description }}</p>
    </div>
    {{ $slot }}
</section>
