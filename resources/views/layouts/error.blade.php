@php
    $storeName = config('aurea-demo.store_settings.store_name', 'Tienda');
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | {{ $storeName }}</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('aurea-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-aurea-paper text-aurea-ink antialiased dark:bg-[#242624] dark:text-[#f1eee8]">
    <main class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
        <section
            class="w-full max-w-xl rounded-3xl border border-aurea-border bg-aurea-surface px-6 py-12 text-center shadow-[0_18px_60px_rgba(92,72,45,0.08)] dark:bg-[#2d302e] sm:px-12 sm:py-16"
            aria-labelledby="error-title">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl border border-aurea-gold-soft bg-[#fbf6ee] text-aurea-focus dark:bg-[#403b34]"
                aria-hidden="true">
                <x-admin.icon name="system-errors" class="h-6 w-6" />
            </div>
            <p class="mt-6 text-[10px] font-semibold uppercase tracking-[.24em] text-aurea-focus">{{ $code }}
            </p>
            <h1 id="error-title" class="mt-3 font-serif text-3xl text-aurea-ink sm:text-4xl">{{ $title }}</h1>
            <p class="mx-auto mt-4 max-w-md text-sm leading-6 text-aurea-muted">{{ $message }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @foreach ($actions as $action)
                    <a href="{{ $action['url'] }}"
                        class="aurea-focus inline-flex cursor-pointer items-center justify-center rounded-lg {{ $loop->first ? 'bg-aurea-gold text-white hover:bg-[#9f793f]' : 'border border-aurea-border text-[#68736c] hover:border-aurea-gold hover:text-aurea-focus dark:text-[#d0d4cf]' }} px-4 py-2.5 text-sm font-medium">
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </section>
    </main>
</body>

</html>
