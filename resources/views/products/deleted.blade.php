@extends('layouts.admin', ['title' => 'Productos eliminados', 'sectionTitle' => 'Productos eliminados'])

@section('content')
    <x-admin.page-header
        :breadcrumbs="[
            ['label' => 'Catálogo', 'url' => route('products.index')],
            ['label' => 'Productos eliminados', 'url' => null],
        ]"
        title="Productos eliminados"
        description="Consulta y restaura productos retirados del catálogo sin perder su información ni su historial."
    >
        <x-slot:actions>
            <a href="{{ route('products.index') }}"
                class="aurea-focus inline-flex w-fit items-center justify-center rounded-lg border border-[#dfe2dc] px-4 py-2.5 text-sm font-medium text-[#68736c] transition hover:border-aurea-gold hover:text-aurea-focus">
                Volver a productos
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.flash-messages />

    <section class="overflow-hidden rounded-2xl border border-aurea-border bg-aurea-surface shadow-[0_5px_20px_rgba(35,42,37,0.03)]"
        aria-labelledby="deleted-products-list-title">
        <div class="border-b border-[#eef0ec] px-5 py-5 sm:px-6">
            <h3 id="deleted-products-list-title" class="font-serif text-xl text-[#28302b]">Papelera</h3>
            <p class="mt-1 text-xs text-[#8a938d]">Mostrando {{ $products->total() }} productos eliminados.</p>
        </div>

        @if ($products->isEmpty())
            <x-admin.empty-state
                title="No hay productos eliminados"
                description="Los productos retirados del catálogo aparecerán aquí."
            />
        @else
            <div class="hidden overflow-x-auto sm:block">
                <table class="admin-table w-full min-w-215 text-left text-sm">
                    <caption class="sr-only">Productos eliminados del catálogo</caption>
                    <thead class="bg-[#fbfbf9] text-[10px] uppercase tracking-wider text-[#9ca49e]">
                        <tr>
                            <th scope="col" class="px-5 py-3 font-medium sm:px-6">Producto</th>
                            <th scope="col" class="px-3 py-3 font-medium">Código</th>
                            <th scope="col" class="px-3 py-3 font-medium">Categoría</th>
                            <th scope="col" class="px-3 py-3 font-medium">Eliminado el</th>
                            <th scope="col" class="px-3 py-3 font-medium">Stock físico</th>
                            <th scope="col" class="px-5 py-3 text-right font-medium sm:px-6">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f0f1ed]">
                        @foreach ($products as $product)
                            @php($productImage = $product->images->first())
                            <tr class="text-[#5b655e] hover:bg-[#fbfbf9]">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <x-admin.product-thumbnail
                                            :image="$productImage?->hasPublicFile() ? ltrim((string) parse_url($productImage->publicUrl(), PHP_URL_PATH), '/') : null"
                                            :alt="$product->name"
                                            size="xs"
                                        />
                                        <div>
                                            <p class="font-medium text-[#343d37]">{{ $product->name }}</p>
                                            <p class="mt-1 text-[11px] text-[#929b94]">{{ $product->model ?: 'Sin modelo' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-xs text-[#929b94]">{{ $product->code }}</td>
                                <td class="px-3 py-4">{{ $product->category?->name ?? 'Sin categoría' }}</td>
                                <td class="px-3 py-4 text-xs text-[#68736c]">{{ $product->deleted_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-4 font-medium text-[#343d37]">{{ $product->physical_stock }}</td>
                                <td class="px-5 py-4 text-right sm:px-6">
                                    <form method="POST" action="{{ route('products.restore', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-gold hover:bg-aurea-paper">
                                            Restaurar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-[#f0f1ed] sm:hidden">
                @foreach ($products as $product)
                    @php($productImage = $product->images->first())
                    <article class="px-5 py-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-admin.product-thumbnail
                                    :image="$productImage?->hasPublicFile() ? ltrim((string) parse_url($productImage->publicUrl(), PHP_URL_PATH), '/') : null"
                                    :alt="$product->name"
                                    size="xs"
                                />
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-[#343d37]">{{ $product->name }}</p>
                                    <p class="mt-1 text-xs text-[#929b94]">{{ $product->code }} · {{ $product->category?->name ?? 'Sin categoría' }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-[#343d37]">{{ $product->physical_stock }}</span>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                            <div>
                                <dt class="text-[#929b94]">Eliminado el</dt>
                                <dd class="mt-0.5 text-[#5b655e]">{{ $product->deleted_at?->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div>
                                <dt class="text-[#929b94]">Stock físico</dt>
                                <dd class="mt-0.5 text-[#5b655e]">{{ $product->physical_stock }}</dd>
                            </div>
                        </dl>
                        <div class="mt-3 flex justify-end">
                            <form method="POST" action="{{ route('products.restore', $product) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="aurea-focus rounded-md px-2 py-1.5 text-xs font-medium text-aurea-gold hover:bg-aurea-paper">
                                    Restaurar
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="border-t border-[#eef0ec] px-5 py-4 sm:px-6">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection
