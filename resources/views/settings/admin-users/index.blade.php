@extends('layouts.admin', ['title' => 'Usuarios administrativos', 'sectionTitle' => 'Usuarios administrativos'])

@section('content')
    @php
        $statusClasses = [
            1 => 'text-[#5b8668]',
            0 => 'text-[#929b94]',
        ];

        $roleLabels = [
            'admin' => 'Administrador',
            'technical' => 'Equipo técnico',
        ];
    @endphp

    <x-admin.page-header :breadcrumbs="[
        [
            'label' => 'Configuración',
            'url' => null,
        ],
        [
            'label' => 'Usuarios administrativos',
            'url' => null,
        ],
    ]" title="Usuarios administrativos"
        description="Consulta y organiza las personas que administran la tienda {{ config('aurea-demo.store_settings.store_name', 'Tienda') }}.">
        <x-slot:actions>
            <a href="{{ route('settings.admin-users.create') }}"
                class="aurea-focus inline-flex w-fit items-center gap-2 rounded-lg bg-aurea-gold px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#9f793f]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                    aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>

                Nuevo usuario
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <section
        class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="admin-users-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="admin-users-list-title" class="font-serif text-xl text-[#28302b]">
                Listado de usuarios
            </h3>

            <p class="mt-1 text-xs text-[#8a938d]">
                Mostrando {{ $users->count() }} de {{ $users->total() }} usuarios administrativos.
            </p>
        </div>

        @if ($users->isEmpty())
            <x-admin.empty-state title="No hay usuarios administrativos todavía"
                description="Agrega un usuario para compartir la administración del panel." :action-url="route('settings.admin-users.create')"
                action-label="Agregar usuario" />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-200 text-left text-sm">
                    <caption class="sr-only">
                        Listado de usuarios administrativos
                    </caption>

                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Usuario</th>
                            <th scope="col" class="px-3 py-3 font-medium">Correo</th>
                            <th scope="col" class="px-3 py-3 font-medium">Rol</th>
                            <th scope="col" class="px-3 py-3 font-medium">Estado</th>
                            <th scope="col" class="px-3 py-3 font-medium">Último acceso</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($users as $user)
                            @php
                                $initials = collect(preg_split('/\s+/', trim($user->name)))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn($namePart) => mb_strtoupper(mb_substr($namePart, 0, 1)))
                                    ->implode('');

                                $statusLabel = $user->status ? 'Activo' : 'Inactivo';
                            @endphp

                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8ddcf] text-xs font-semibold text-[#8f6d43]">
                                            {{ $initials }}
                                        </span>

                                        <span class="font-medium text-[#343d37]">
                                            {{ $user->name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-3 py-4">
                                    {{ $user->email }}
                                </td>

                                <td class="px-3 py-4">
                                    {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                                </td>

                                <td class="px-3 py-4">
                                    <x-admin.status-badge :label="$statusLabel" :status-class="$statusClasses[$user->status ? 1 : 0]" />
                                </td>

                                <td class="whitespace-nowrap px-3 py-4 text-xs text-[#68736c]">
                                    {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Sin acceso registrado' }}
                                </td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <x-admin.row-actions :label="$user->name" <x-admin.row-actions :label="$user->name"
                                        :edit-url="route('settings.admin-users.edit', $user)" :delete-url="route('settings.admin-users.destroy', $user)" delete-label="usuario administrativo" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] sm:hidden">
                @foreach ($users as $user)
                    @php
                        $initials = collect(preg_split('/\s+/', trim($user->name)))
                            ->filter()
                            ->take(2)
                            ->map(fn($namePart) => mb_strtoupper(mb_substr($namePart, 0, 1)))
                            ->implode('');

                        $statusLabel = $user->status ? 'Activo' : 'Inactivo';
                    @endphp

                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#e8ddcf] text-xs font-semibold text-[#8f6d43]">
                                    {{ $initials }}
                                </span>

                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-[#343d37]">
                                        {{ $user->name }}
                                    </p>

                                    <p class="mt-1 truncate text-xs text-[#929b94]">
                                        {{ $user->email }}
                                    </p>
                                </div>
                            </div>

                            <x-admin.status-badge :label="$statusLabel" :status-class="$statusClasses[$user->status ? 1 : 0]" />
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-xs">
                            <div>
                                <dt class="text-[#929b94]">Rol</dt>

                                <dd class="mt-0.5 text-[#5b655e]">
                                    {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-[#929b94]">Último acceso</dt>

                                <dd class="mt-0.5 text-[#5b655e]">
                                    {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Sin acceso registrado' }}
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex justify-end">
                            <x-admin.row-actions :label="$user->name" :edit-url="route('settings.admin-users.edit', $user)" :delete-url="route('settings.admin-users.destroy', $user)"
                                delete-label="usuario administrativo" />
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
                {{ $users->links() }}
            </div>
        @endif
    </section>
@endsection
