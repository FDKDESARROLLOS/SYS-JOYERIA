@if (session('status'))
    <div
        data-flash-success
        class="mb-7 flex max-h-40 items-start justify-between gap-4 overflow-hidden rounded-xl border border-[#d8e7dc] bg-[#f4faf5] px-4 py-3 text-sm text-[#4f765a] transition-[opacity,max-height,margin] duration-300 ease-out dark:border-[#3f5b46] dark:bg-[#243329] dark:text-[#b8d8bd]"
        role="status"
        aria-live="polite"
    >
        <span>{{ session('status') }}</span>
        <button
            type="button"
            class="aurea-focus shrink-0 text-lg leading-none text-[#4f765a] transition-colors hover:text-[#34543d] dark:text-[#b8d8bd] dark:hover:text-white"
            aria-label="Cerrar mensaje"
            data-flash-dismiss
        >
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if ($errors->any())
    <div
        data-flash-error
        class="mb-7 flex max-h-40 items-start justify-between gap-4 overflow-hidden rounded-xl border border-[#efd8d3] bg-[#fdf6f4] px-4 py-3 text-sm text-[#94594e] transition-[opacity,max-height,margin] duration-300 ease-out dark:border-[#69433e] dark:bg-[#3a2927] dark:text-[#f0b8ad]"
        role="alert"
    >
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button
            type="button"
            class="aurea-focus shrink-0 text-lg leading-none text-[#94594e] transition-colors hover:text-[#6f3e36] dark:text-[#f0b8ad] dark:hover:text-white"
            aria-label="Cerrar mensaje"
            data-flash-dismiss
        >
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@pushOnce('scripts')
    <script>
        (() => {
            const dismissFlash = (flash) => {
                if (flash.dataset.flashDismissed) {
                    return;
                }

                flash.dataset.flashDismissed = 'true';
                flash.classList.add('max-h-0', 'mb-0', 'opacity-0');

                window.setTimeout(() => flash.remove(), 300);
            };

            document.querySelectorAll('[data-flash-success], [data-flash-error]').forEach((flash) => {
                flash.querySelector('[data-flash-dismiss]')?.addEventListener('click', () => dismissFlash(flash));

                if (flash.hasAttribute('data-flash-success')) {
                    window.setTimeout(() => dismissFlash(flash), 4000);
                }
            });
        })();
    </script>
@endPushOnce
