<?php

namespace App\Http\Controllers;

use App\Models\ErrorCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ErrorCodeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $module = trim((string) $request->query('module', ''));
        $severity = $request->query('severity');
        $status = $request->query('status');

        $errorCodes = ErrorCode::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when(in_array($severity, ['error', 'warning', 'info'], true), fn ($query) => $query->where('severity', $severity))
            ->when(in_array($status, ['0', '1'], true), fn ($query) => $query->where('status', $status === '1'))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('settings.errors', [
            'errorCodes' => $errorCodes,
            'modules' => ErrorCode::query()
                ->select('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
            'summary' => [
                'total' => ErrorCode::query()->count(),
                'error' => ErrorCode::query()->where('severity', 'error')->count(),
                'warning' => ErrorCode::query()->where('severity', 'warning')->count(),
                'info' => ErrorCode::query()->where('severity', 'info')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('settings.error-codes.create', [
            'errorCode' => new ErrorCode([
                'status' => true,
                'severity' => 'error',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ErrorCode::create($request->validate($this->rules()));

        return redirect()
            ->route('settings.errors')
            ->with('status', 'Código de error creado correctamente.');
    }

    public function edit(ErrorCode $errorCode): View
    {
        return view('settings.error-codes.edit', [
            'errorCode' => $errorCode,
        ]);
    }

    public function update(Request $request, ErrorCode $errorCode): RedirectResponse
    {
        $errorCode->update($request->validate($this->rules($errorCode)));

        return redirect()
            ->route('settings.errors')
            ->with('status', 'Código de error actualizado correctamente.');
    }

    public function toggleStatus(ErrorCode $errorCode): RedirectResponse
    {
        $errorCode->update([
            'status' => ! $errorCode->status,
        ]);

        return redirect()
            ->route('settings.errors')
            ->with('status', 'Estado del código de error actualizado correctamente.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(?ErrorCode $errorCode = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('error_codes', 'code')->ignore($errorCode?->id),
            ],
            'module' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', Rule::in(['error', 'warning', 'info'])],
            'status' => ['required', 'boolean'],
        ];
    }
}
