<?php

namespace App\Http\Controllers;

use App\Models\Stone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class StoneController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        $stones = Stone::query()
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

        return view('stones.index', [
            'stones' => $stones,
        ]);
    }

    public function create(): View
    {
        return view('stones.create', [
            'stone' => new Stone([
                'status' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['slug'] = $this->uniqueSlug($validated['name']);

        Stone::create($validated);

        return redirect()
            ->route('stones.index')
            ->with('status', 'Piedra creada correctamente.');
    }

    public function edit(Stone $stone): View
    {
        return view('stones.edit', [
            'stone' => $stone,
        ]);
    }

    public function update(Request $request, Stone $stone): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($validated['name'] !== $stone->name) {
            $validated['slug'] = $this->uniqueSlug($validated['name'], $stone);
        }

        $stone->update($validated);

        return redirect()
            ->route('stones.index')
            ->with('status', 'Piedra actualizada correctamente.');
    }

    public function destroy(Stone $stone): RedirectResponse
    {
        try {
            DB::transaction(function () use ($stone): void {
                if (! $stone->delete()) {
                    throw new RuntimeException('No se pudo eliminar la piedra.');
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors([
                'stone' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('stones.index')
            ->with('status', 'Piedra eliminada correctamente.');
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

    private function uniqueSlug(string $name, ?Stone $stone = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Stone::withTrashed()
                ->where('slug', $slug)
                ->when($stone, fn ($query) => $query->whereKeyNot($stone->getKey()))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
