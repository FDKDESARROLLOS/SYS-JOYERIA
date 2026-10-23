<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use App\Support\ErrorContextSanitizer;
use App\Support\ErrorLogStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ErrorLogController extends Controller
{
    public function index(Request $request, ErrorContextSanitizer $sanitizer): View
    {
        $search = trim((string) $request->query('search', ''));
        $module = trim((string) $request->query('module', ''));
        $source = trim((string) $request->query('source', ''));
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $errorLogs = ErrorLog::query()
            ->with([
                'errorCode:id,code',
                'user:id,name',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('module', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhereHas('errorCode', fn ($query) => $query->where('code', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($source !== '', fn ($query) => $query->where('source', $source))
            ->when(in_array($status, ErrorLogStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($dateFrom !== null && $dateFrom !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $dateFrom))
            ->when($dateTo !== null && $dateTo !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $dateTo))
            ->latest('occurred_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $errorLogs->getCollection()->transform(function (ErrorLog $errorLog) use ($sanitizer): ErrorLog {
            $errorLog->setAttribute('context', $sanitizer->sanitize($errorLog->context ?? []));

            return $errorLog;
        });

        return view('settings.error-logs', [
            'errorLogs' => $errorLogs,
            'modules' => ErrorLog::query()
                ->whereNotNull('module')
                ->select('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
            'sources' => ErrorLog::query()
                ->select('source')
                ->distinct()
                ->orderBy('source')
                ->pluck('source'),
            'statuses' => ErrorLogStatus::labels(),
            'summary' => [
                'total' => ErrorLog::query()->count(),
                'pending' => ErrorLog::query()->where('status', ErrorLogStatus::PENDING)->count(),
                'reviewed' => ErrorLog::query()->where('status', ErrorLogStatus::REVIEWED)->count(),
                'resolved' => ErrorLog::query()->where('status', ErrorLogStatus::RESOLVED)->count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, ErrorLog $errorLog): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ErrorLogStatus::values())],
        ]);

        if (! ErrorLogStatus::canTransition($errorLog->status, $validated['status'])) {
            throw ValidationException::withMessages([
                'status' => 'La transición de estado seleccionada no es válida.',
            ]);
        }

        $errorLog->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('settings.errors.logs')
            ->with('status', 'Estado del error actualizado correctamente.');
    }
}
