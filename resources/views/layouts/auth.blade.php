@php
    $storeName = config('aurea-demo.store_settings.store_name', 'Tienda');
@endphp

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Acceso' }} | {{ $storeName }}</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('aurea-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-aurea-paper text-aurea-ink antialiased dark:bg-[#242624] dark:text-[#f1eee8]">
    <main class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8">
        <div
            class="grid w-full max-w-5xl overflow-hidden rounded-3xl border border-aurea-border bg-white shadow-[0_18px_60px_rgba(92,72,45,0.08)] dark:bg-[#2d302e] lg:grid-cols-[.85fr_1fr]">
            <section class="hidden bg-[#fbf9f6] p-10 dark:bg-[#343633] lg:flex lg:flex-col lg:justify-between xl:p-14"
                aria-label="Información de la tienda">
                <div>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full border border-aurea-gold text-sm font-medium text-aurea-focus">Logo</span>
                        <div>
                            <p class="font-brand text-2xl tracking-[.16em] text-[#222222] dark:text-[#eeeae2]">
                                {{ $storeName }}</p>
                            <p class="text-[9px] uppercase tracking-[.3em] text-aurea-muted">Administración</p>
                        </div>
                    </div>
                    <div class="mt-24 max-w-xs">
                        <p class="text-[10px] font-semibold uppercase tracking-[.22em] text-aurea-focus">Panel
                            administrativo</p>
                        {{-- <h1 class="mt-4 font-brand text-5xl leading-tight text-[#302a24] dark:text-[#f1eee8]">Una
                            gestión clara para cada detalle.</h1> --}}
                        {{-- <p class="mt-5 text-sm leading-6 text-aurea-muted">Administra tu catálogo y la operación diaria de tu tienda desde un espacio pensado para trabajar con calma.</p> --}}
                    </div>
                </div>
                <p class="text-xs text-[#a39a8e]">Acceso exclusivo para el equipo administrativo.</p>
            </section>

            <section class="flex items-center justify-center p-6 sm:p-10 lg:p-14" aria-labelledby="auth-title">
                <div class="w-full max-w-md">
                    <div class="mb-8 lg:hidden">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-aurea-gold text-sm font-medium text-aurea-focus">Logo</span>
                            <div>
                                <p class="font-brand text-2xl tracking-[.16em] text-[#222222] dark:text-[#eeeae2]">
                                    {{ $storeName }}</p>
                                <p class="text-[9px] uppercase tracking-[.3em] text-aurea-muted">Administración</p>
                            </div>
                        </div>
                    </div>

                    @yield('content')
                </div>
            </section>
        </div>
    </main>

    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            const passwordInput = document.getElementById(toggle.dataset.target);
            const showIcon = toggle.querySelector('[data-password-show]');
            const hideIcon = toggle.querySelector('[data-password-hide]');

            toggle.addEventListener('click', () => {
                const isVisible = passwordInput.type === 'text';

                passwordInput.type = isVisible ? 'password' : 'text';
                toggle.setAttribute('aria-label', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                toggle.setAttribute('title', isVisible ? 'Mostrar contraseña' : 'Ocultar contraseña');
                toggle.setAttribute('aria-pressed', String(!isVisible));
                showIcon.classList.toggle('hidden', !isVisible);
                hideIcon.classList.toggle('hidden', isVisible);
            });
        });
    </script>
</body>

</html>
