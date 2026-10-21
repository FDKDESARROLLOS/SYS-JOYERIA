<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $materials = Material::query()
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

        return view('materials.index', [
            'materials' => $materials,
        ]);
    }

    public function create(): View
    {
        return view('materials.create', [
            'material' => new Material([
                'status' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        Material::create($validated);

        return redirect()
            ->route('materials.index')
            ->with('status', 'Material creado correctamente.');
    }

    public function edit(Material $material): View
    {
        return view('materials.edit', [
            'material' => $material,
        ]);
    }

    public function update(Request $request, Material $material): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($validated['name'] !== $material->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $material);
        }

        $material->update($validated);

        return redirect()
            ->route('materials.index')
            ->with('status', 'Material actualizado correctamente.');
    }

    public function destroy(Material $material): RedirectResponse
    {
        try {
            DB::transaction(function () use ($material): void {
                if (! $material->delete()) {
                    throw new RuntimeException('No se pudo eliminar el material.');
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'material' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('materials.index')
            ->with('status', 'Material eliminado correctamente.');
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

    private function uniqueSlug(string $name, ?Material $material = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Material::withTrashed()
                ->where('slug', $slug)
                ->when($material, fn ($query) => $query->whereKeyNot($material->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
