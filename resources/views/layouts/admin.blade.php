<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} | {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}</title>
    <script>
        (() => {
            const savedTheme = localStorage.getItem('aurea-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('build/assets/app-BgnIzCXW.css') }}">
    <script src="{{ asset('build/assets/app-Dfds1csW.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="bg-aurea-paper text-aurea-ink antialiased">
    @php
        $storeName = config('aurea-demo.store_settings.store_name', 'Tienda');
        $navigationGroups = [
            [
                'label' => null,
                'items' => [
                    [
                        'label' => 'Dashboard',
                        'icon' => 'dashboard',
                        'route' => 'dashboard',
                        'active' => 'dashboard',
                    ],
                ],
            ],
            [
                'label' => 'Catálogo',
                'items' => [
                    [
                        'label' => 'Productos',
                        'icon' => 'products',
                        'route' => 'products.index',
                        'active' => 'products.index',
                    ],
                    [
                        'label' => 'Categorías',
                        'icon' => 'categories',
                        'route' => 'categories.index',
                        'active' => 'categories.index',
                    ],
                    [
                        'label' => 'Colecciones',
                        'icon' => 'collections',
                        'route' => 'collections.index',
                        'active' => 'collections.index',
                    ],
                    [
                        'label' => 'Materiales',
                        'icon' => 'materials',
                        'route' => 'materials.index',
                        'active' => 'materials.index',
                    ],
                    [
                        'label' => 'Piedras',
                        'icon' => 'stones',
                        'route' => 'stones.index',
                        'active' => 'stones.index',
                    ],
                    [
                        'label' => 'Tallas',
                        'icon' => 'sizes',
                        'route' => 'sizes.index',
                        'active' => 'sizes.index',
                    ],
                ],
            ],
            [
                'label' => 'Ventas',
                'items' => [
                    [
                        'label' => 'Pedidos',
                        'icon' => 'orders',
                        'route' => 'orders.index',
                        'active' => 'orders.index',
                    ],
                ],
            ],
            [
                'label' => 'Inventario',
                'items' => [
                    [
                        'label' => 'Existencias',
                        'icon' => 'inventory',
                        'route' => 'inventory.stock',
                        'active' => 'inventory.stock',
                    ],
                    [
                        'label' => 'Movimientos',
                        'icon' => 'movements',
                        'route' => 'inventory.movements.index',
                        'active' => 'inventory.movements.index',
                    ],
                ],
            ],
            [
                'label' => 'Configuración',
                'items' => [
                    [
                        'label' => 'Datos de la tienda',
                        'icon' => 'store',
                        'route' => 'settings.store',
                        'active' => 'settings.store',
                    ],
                    [
                        'label' => 'Pagos',
                        'icon' => 'payments',
                        'route' => 'settings.payments',
                        'active' => 'settings.payments',
                    ],
                    [
                        'label' => 'Envíos',
                        'icon' => 'shipping',
                        'route' => 'settings.shipping',
                        'active' => 'settings.shipping',
                    ],
                    [
                        'label' => 'Catálogo de errores',
                        'icon' => 'system-errors',
                        'route' => 'settings.errors',
                        'active' => 'settings.errors',
                    ],
                    [
                        'label' => 'Registro de errores',
                        'icon' => 'system-errors',
                        'route' => 'settings.errors.logs',
                        'active' => 'settings.errors.logs',
                    ],
                    [
                        'label' => 'Usuarios administrativos',
                        'icon' => 'users',
                        'route' => 'settings.admin-users.index',
                        'active' => 'settings.admin-users.*',
                    ],
                ],
            ],
        ];
    @endphp
    <div class="min-h-screen">
        <div id="sidebar-backdrop"
            class="fixed inset-0 z-30 hidden bg-[#222222]/20 backdrop-blur-sm dark:bg-[#111412]/50 lg:hidden"></div>

        <aside id="admin-sidebar"
            class="fixed inset-y-0 left-0 z-40 flex h-screen max-h-screen min-h-dvh w-66 -translate-x-full flex-col border-r border-aurea-border bg-aurea-sidebar text-[#222222] transition-transform duration-300 dark:text-[#eeeae2] lg:translate-x-0">

            <div class="flex h-22 items-center border-b border-aurea-border px-7">

                <div class="flex items-center gap-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full border border-aurea-gold/70 text-aurea-focus">
                        Logo
                    </div>

                    <div>
                        <p class="font-brand text-xl tracking-[.18em] text-[#222222] dark:text-[#eeeae2]">
                            {{ $storeName }}</p>
                        <p class="text-[9px] uppercase tracking-[.3em] text-[#78716c]">Administración</p>
                    </div>
                </div>

                <button type="button" id="close-sidebar"
                    class="aurea-focus ml-auto rounded-lg p-1 text-[#78716c] hover:bg-[#eeeae4] hover:text-[#222222] lg:hidden"
                    aria-label="Cerrar menú">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                        <path d="m6 6 12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <nav class="scrollbar-hidden flex-1 overflow-y-auto px-4 py-6" aria-label="Navegación principal">
                @foreach ($navigationGroups as $navigationGroup)
                    <div class="{{ $navigationGroup['label'] ? 'mb-6' : 'mb-7' }}">
                        @if ($navigationGroup['label'])
                            <p class="px-4 pb-2 text-[10px] font-semibold uppercase tracking-[.18em] text-[#78716c]">
                                {{ $navigationGroup['label'] }}
                            </p>
                        @endif
                        <div class="space-y-1">
                            @foreach ($navigationGroup['items'] as $navigationItem)
                                @php
                                    $isActive =
                                        $navigationItem['active'] &&
                                        (request()->routeIs($navigationItem['active']) ||
                                            ($navigationItem['route'] === 'dashboard' && request()->is('/')));
                                @endphp
                                @if ($navigationItem['route'])
                                    <a href="{{ route($navigationItem['route']) }}"
                                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                                        class="aurea-focus flex w-full items-center gap-3 rounded-lg px-4 py-2 text-[13px] transition {{ $isActive ? 'bg-[#ede3d5] text-[#222222] dark:bg-[#4a4034] dark:text-[#f1eee8]' : 'text-[#4f4a45] hover:bg-[#f7f4ef] hover:text-[#222222] dark:text-[#c2c6c1] dark:hover:bg-[#3a3b37] dark:hover:text-[#f1eee8]' }}">
                                        <x-admin.icon :name="$navigationItem['icon']"
                                            class="{{ $navigationItem['label'] === 'Dashboard' ? 'h-4.25 w-4.25' : 'h-4 w-4' }} shrink-0" />
                                        {{ $navigationItem['label'] }}
                                    </a>
                                @else
                                    <button type="button"
                                        class="aurea-focus flex w-full items-center gap-3 rounded-lg px-4 py-2 text-left text-[13px] text-[#4f4a45] transition hover:bg-[#f7f4ef] hover:text-[#222222] dark:text-[#c2c6c1] dark:hover:bg-[#3a3b37] dark:hover:text-[#f1eee8]">
                                        <x-admin.icon :name="$navigationItem['icon']" />
                                        {{ $navigationItem['label'] }}
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
            @php
                $sidebarUser = auth()->user();

                $sidebarInitials = collect(preg_split('/\s+/', trim($sidebarUser->name)))
                    ->filter()
                    ->take(2)
                    ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                    ->implode('');

                $sidebarRole = match ($sidebarUser->role) {
                    'admin' => 'Administrador',
                    'technical' => 'Equipo técnico',
                    default => ucfirst($sidebarUser->role),
                };
            @endphp

            <div class="border-t border-aurea-border p-4">
                <div class="flex items-center gap-3 rounded-xl bg-[#fdfcfb] p-3 dark:bg-[#343633]">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-aurea-gold-soft text-xs font-semibold text-[#5a4630]">
                        {{ $sidebarInitials }}
                    </div>

                    <div class="min-w-0">
                        <p class="truncate text-sm text-[#222222] dark:text-[#eeeae2]">
                            {{ $sidebarUser->name }}
                        </p>

                        <p class="text-[11px] text-[#78716c]">
                            {{ $sidebarRole }}
                        </p>
                    </div>

                    <svg class="ml-auto h-4 w-4 text-[#78716c]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7">
                        <path d="m8 10 4 4 4-4" />
                    </svg>
                </div>
            </div>
        </aside>

        @php
            $headerUser = auth()->user();

            $headerInitials = collect(preg_split('/\s+/', trim($headerUser->name)))
                ->filter()
                ->take(2)
                ->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))
                ->implode('');

            $headerRole = match ($headerUser->role) {
                'admin' => 'Administrador',
                'technical' => 'Equipo técnico',
                default => ucfirst($headerUser->role),
            };
        @endphp

        <div class="min-h-screen min-w-0 lg:ml-66">
            <header
                class="flex h-22 items-center justify-between border-b border-aurea-border bg-white px-5 sm:px-8 dark:bg-[#2a2c2a] lg:px-10">

                <div class="flex items-center gap-4">
                    <button type="button" id="open-sidebar"
                        class="aurea-focus rounded-lg p-2 text-[#65716a] hover:bg-[#f1f2ee] lg:hidden"
                        aria-label="Abrir menú">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                </div>
                <div class="flex items-center gap-3 sm:gap-5">

                    <button type="button" id="theme-toggle"
                        class="aurea-focus rounded-full p-2 text-[#647069] hover:bg-[#f1f2ee] dark:text-[#d7c29e] dark:hover:bg-[#3a3b37]"
                        aria-label="Cambiar a modo oscuro" aria-pressed="false">

                        <svg class="h-5 w-5 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.6" aria-hidden="true">
                            <circle cx="12" cy="12" r="4" />
                            <path
                                d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42" />
                        </svg>

                        <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.6" aria-hidden="true">
                            <path d="M20.5 14.5A8.5 8.5 0 0 1 9.5 3.5 8.5 8.5 0 1 0 20.5 14.5Z" />
                        </svg>
                    </button>

                    <div class="hidden h-7 w-px bg-aurea-border sm:block"></div>

                    <div id="profile-menu-container" class="relative">

                        <button type="button" id="profile-menu-button" aria-label="Abrir menú de usuario"
                            aria-haspopup="menu" aria-expanded="false" aria-controls="profile-menu"
                            class="aurea-focus flex items-center gap-2 rounded-lg p-1 hover:bg-[#f1f2ee] dark:hover:bg-[#3a3b37]">

                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-[#eadfce] text-xs font-semibold text-[#806441]">
                                {{ $headerInitials }}
                            </div>

                            <span class="hidden text-sm font-medium text-aurea-ink sm:block">
                                {{ $headerUser->name }}
                            </span>

                            <svg class="hidden h-4 w-4 text-aurea-muted sm:block" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="m8 10 4 4 4-4" />
                            </svg>
                        </button>

                        <div id="profile-menu"
                            class="absolute right-0 top-full z-50 mt-2 hidden w-64 max-w-[calc(100vw-2rem)] rounded-xl border border-aurea-border bg-white p-2 shadow-[0_12px_30px_rgba(35,42,37,0.12)] dark:bg-[#343633]"
                            role="menu" aria-labelledby="profile-menu-button">

                            <div class="flex items-center gap-3 rounded-lg px-3 py-2.5">

                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#eadfce] text-xs font-semibold text-[#806441]">
                                    {{ $headerInitials }}
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-aurea-ink">
                                        {{ $headerUser->name }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-aurea-muted">
                                        {{ $headerRole }}
                                    </p>
                                </div>
                            </div>

                            <div class="my-2 border-t border-aurea-border"></div>

                            <div class="space-y-1" aria-label="Acciones de perfil">

                                <a href="{{ route('profile') }}" role="menuitem"
                                    class="aurea-focus flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[#56625a] hover:bg-aurea-paper hover:text-aurea-focus dark:text-[#d0d4cf] dark:hover:bg-[#3a3b37]">
                                    Mi perfil
                                </a>

                                <a href="{{ route('profile.password') }}" role="menuitem"
                                    class="aurea-focus flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-[#56625a] hover:bg-aurea-paper hover:text-aurea-focus dark:text-[#d0d4cf] dark:hover:bg-[#3a3b37]">
                                    Cambiar contraseña
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <button type="submit" role="menuitem"
                                        class="aurea-focus flex w-full items-center rounded-lg px-3 py-2 text-left text-sm text-aurea-danger hover:bg-[#f9e9e6]">
                                        Cerrar sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <main class="mx-auto max-w-375 px-5 py-7 sm:px-8 lg:px-10 lg:py-9">
                @yield('content')
            </main>
        </div>
    </div>
    <x-admin.delete-confirmation />
    @stack('scripts')
    <script>
        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const openSidebarButton = document.getElementById('open-sidebar');
        const closeSidebarButton = document.getElementById('close-sidebar');
        const profileMenuContainer = document.getElementById('profile-menu-container');
        const profileMenuButton = document.getElementById('profile-menu-button');
        const profileMenu = document.getElementById('profile-menu');
        const profileMenuItems = profileMenu ?
            Array.from(profileMenu.querySelectorAll('[role="menuitem"]')) : [];

        const toggleSidebar = (open) => {
            sidebar.classList.toggle('-translate-x-full', !open);
            backdrop.classList.toggle('hidden', !open);
        };

        openSidebarButton?.addEventListener('click', () => toggleSidebar(true));
        closeSidebarButton?.addEventListener('click', () => toggleSidebar(false));
        backdrop?.addEventListener('click', () => toggleSidebar(false));

        const toggleProfileMenu = (open) => {
            if (!profileMenu || !profileMenuButton) {
                return;
            }

            profileMenu.classList.toggle('hidden', !open);
            profileMenuButton.setAttribute('aria-expanded', String(open));

            if (open) {
                profileMenuItems[0]?.focus();
            }
        };

        profileMenuButton?.addEventListener('click', () => {
            const isOpen = profileMenuButton.getAttribute('aria-expanded') === 'true';

            toggleProfileMenu(!isOpen);
        });

        profileMenuItems.forEach((item) => {
            item.addEventListener('click', () => toggleProfileMenu(false));
        });

        const deleteConfirmation = document.getElementById('delete-confirmation');
        const deleteConfirmationTitle = document.getElementById('delete-confirmation-title');
        const deleteConfirmationCancel = document.getElementById('delete-confirmation-cancel');
        const deleteConfirmationConfirm = document.getElementById('delete-confirmation-confirm');
        const deleteTriggers = Array.from(document.querySelectorAll('[data-delete-trigger]'));
        const feminineDeleteLabels = new Set(['variante', 'categoría', 'colección', 'piedra', 'talla']);
        let deleteTrigger = null;

        const toggleDeleteConfirmation = (open) => {
            if (!deleteConfirmation) {
                return;
            }

            deleteConfirmation.classList.toggle('hidden', !open);
            deleteConfirmation.classList.toggle('flex', open);

            if (!open) {
                deleteTrigger?.focus();
                deleteTrigger = null;
            }
        };

        deleteTriggers.forEach((trigger) => {
            trigger.addEventListener('click', () => {
                deleteTrigger = trigger;
                const label = trigger.dataset.deleteLabel;
                const article = feminineDeleteLabels.has(label) ? 'esta' : 'este';

                deleteConfirmationTitle.textContent = `¿Eliminar ${article} ${label}?`;
                toggleDeleteConfirmation(true);
                deleteConfirmationCancel?.focus();
            });
        });

        deleteConfirmationCancel?.addEventListener('click', () => toggleDeleteConfirmation(false));
        deleteConfirmationConfirm?.addEventListener('click', () => {
            if (!deleteTrigger) {
                return;
            }

            const deleteUrl = deleteTrigger.dataset.deleteUrl;

            if (!deleteUrl) {
                return;
            }

            const form = document.createElement('form');

            form.method = 'POST';
            form.action = deleteUrl;

            form.innerHTML = `
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="_method" value="DELETE">
    `;

            document.body.appendChild(form);

            form.submit();
        });
        document.addEventListener('click', (event) => {
            if (profileMenuContainer && !profileMenuContainer.contains(event.target)) {
                toggleProfileMenu(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const profileMenuWasOpen = profileMenuButton?.getAttribute('aria-expanded') === 'true';

                toggleSidebar(false);
                toggleProfileMenu(false);
                toggleDeleteConfirmation(false);

                if (profileMenuWasOpen) {
                    profileMenuButton?.focus();
                }
            }

            if (profileMenuButton?.getAttribute('aria-expanded') !== 'true' || !profileMenuItems.length) {
                return;
            }

            const currentIndex = profileMenuItems.indexOf(document.activeElement);

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                const nextIndex = (currentIndex + direction + profileMenuItems.length) % profileMenuItems.length;

                profileMenuItems[nextIndex]?.focus();
            }

            if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                profileMenuItems[event.key === 'Home' ? 0 : profileMenuItems.length - 1]?.focus();
            }
        });
        document.querySelectorAll('[data-loading-button]').forEach((button) => {
            const form = button.closest('form');

            form?.addEventListener('submit', () => {
                button.disabled = true;
                button.querySelector('[data-button-label]').textContent = button.dataset.loadingLabel;
                button.querySelector('[data-button-spinner]').classList.remove('hidden');
            });
        });

        document.querySelectorAll('[data-clear-filters]').forEach((button) => {
            button.addEventListener('click', () => {
                const section = button.closest('[data-filter-section]');

                section?.querySelectorAll('input, select').forEach((control) => {
                    control.value = '';
                });

                section?.querySelector('input')?.focus();
                section?.dispatchEvent(new CustomEvent('filters-cleared'));
            });
        });
    </script>
</body>

</html>
