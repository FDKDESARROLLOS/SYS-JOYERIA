@if (session('status'))
    <div
        class="mb-7 rounded-xl border border-[#d8e7dc] bg-[#f4faf5] px-4 py-3 text-sm text-[#4f765a] dark:border-[#3f5b46] dark:bg-[#243329] dark:text-[#b8d8bd]"
        role="status"
        aria-live="polite"
    >
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div
        class="mb-7 rounded-xl border border-[#efd8d3] bg-[#fdf6f4] px-4 py-3 text-sm text-[#94594e] dark:border-[#69433e] dark:bg-[#3a2927] dark:text-[#f0b8ad]"
        role="alert"
    >
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
