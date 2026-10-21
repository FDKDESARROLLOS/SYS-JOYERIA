<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $collections = Collection::query()
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
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('collections.index', [
            'collections' => $collections,
        ]);
    }

    public function create(): View
    {
        return view('collections.create', [
            'collection' => new Collection([
                'status' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        Collection::create($validated);

        return redirect()
            ->route('collections.index')
            ->with('status', 'Colección creada correctamente.');
    }

    public function edit(Collection $collection): View
    {
        return view('collections.edit', [
            'collection' => $collection,
        ]);
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($validated['name'] !== $collection->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $collection);
        }

        $collection->update($validated);

        return redirect()
            ->route('collections.index')
            ->with('status', 'Colección actualizada correctamente.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $collection->delete();

        return redirect()
            ->route('collections.index')
            ->with('status', 'Colección eliminada correctamente.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ];
    }

    private function uniqueSlug(string $name, ?Collection $collection = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Collection::withTrashed()
                ->where('slug', $slug)
                ->when($collection, fn ($query) => $query->whereKeyNot($collection->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
