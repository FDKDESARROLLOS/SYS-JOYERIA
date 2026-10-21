<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Size;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class SizeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $sizes = Size::query()
            ->with('categories:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(in_array($status, ['0', '1'], true), function ($query) use ($status): void {
                $query->where('status', $status === '1');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('sizes.index', [
            'sizes' => $sizes,
        ]);
    }

    public function create(): View
    {
        return view('sizes.create', [
            'size' => new Size([
                'status' => true,
            ]),
            'categories' => $this->availableCategories(),
            'selectedCategoryIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validatedCategoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);
        $validated['sort_order'] = $this->nextSortOrder();

        DB::transaction(function () use ($validated, $validatedCategoryIds): void {
            $size = Size::create($validated);
            $size->categories()->sync($validatedCategoryIds);
        });

        return redirect()
            ->route('sizes.index')
            ->with('status', 'Talla creada correctamente.');
    }

    public function edit(Size $size): View
    {
        return view('sizes.edit', [
            'size' => $size,
            'categories' => $this->availableCategories(),
            'selectedCategoryIds' => $size->categories()
                ->whereNull('parent_id')
                ->pluck('categories.id')
                ->all(),
        ]);
    }

    public function update(Request $request, Size $size): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validatedCategoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        DB::transaction(function () use ($size, $validated, $validatedCategoryIds): void {
            $size->update($validated);
            $size->categories()->sync($validatedCategoryIds);
        });

        return redirect()
            ->route('sizes.index')
            ->with('status', 'Talla actualizada correctamente.');
    }

    public function destroy(Size $size): RedirectResponse
    {
        try {
            DB::transaction(function () use ($size): void {
                if (! $size->delete()) {
                    throw new RuntimeException('No se pudo eliminar la talla.');
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'size' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('sizes.index')
            ->with('status', 'Talla eliminada correctamente.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => [
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('parent_id')
                    ->where('status', true)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    private function availableCategories(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where('status', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function nextSortOrder(): int
    {
        return (Size::max('sort_order') ?? -1) + 1;
    }
}
