<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $categories = Category::query()
            ->with('parent:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['0', '1'], true), function ($query) use ($status): void {
                $query->where('status', $status === '1');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('categories.create', [
            'category' => new Category([
                'status' => true,
            ]),
            'parentCategories' => $this->parentCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['slug'] = $this->uniqueSlug($validated['name']);
        $validated['sort_order'] = $this->nextSortOrder();

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Categoría creada correctamente.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', [
            'category' => $category,
            'parentCategories' => $this->parentCategories($category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate($this->rules($category));

        if ($validated['name'] !== $category->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $category);
        }

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Categoría actualizada correctamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            DB::transaction(function () use ($category): void {
                if ($category->parent_id === null) {
                    $this->softDeleteDescendants($category);
                }

                if (! $category->delete()) {
                    throw new RuntimeException('No se pudo eliminar la categoría.');
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'category' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('status', 'Categoría eliminada correctamente.');
    }

    private function softDeleteDescendants(Category $category): void
    {
        $category->children()
            ->withTrashed()
            ->get()
            ->each(function (Category $child): void {
                $this->softDeleteDescendants($child);

                if (! $child->trashed() && ! $child->delete()) {
                    throw new RuntimeException('No se pudo eliminar una subcategoría.');
                }
            });
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?Category $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->whereNull('parent_id')
                    ->whereNull('deleted_at'),
                Rule::notIn([$category?->id]),
            ],
        ];
    }

    private function nextSortOrder(): int
    {
        return (Category::max('sort_order') ?? -1) + 1;
    }

    private function uniqueSlug(string $name, ?Category $category = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Category::withTrashed()
                ->where('slug', $slug)
                ->when($category, fn ($query) => $query->whereKeyNot($category->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @return Collection<int, Category>
     */
    private function parentCategories(?Category $category = null): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->when($category, fn ($query) => $query->whereKeyNot($category->getKey()))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
