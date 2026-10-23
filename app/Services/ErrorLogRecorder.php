<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Support\ErrorContextSanitizer;
use App\Support\ErrorLogStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorLogRecorder
{
    public function __construct(
        private readonly ErrorContextSanitizer $sanitizer,
    ) {}

    public function record(Throwable $exception, ?Request $request = null): void
    {
        if ($this->shouldIgnore($exception)) {
            return;
        }

        try {
            $route = $request?->route();
            $routeName = is_object($route) ? $route->getName() : null;

            ErrorLog::create([
                'error_code_id' => null,
                'user_id' => $request?->user()?->getKey(),
                'source' => 'backend',
                'module' => $this->moduleFromRoute($routeName),
                'action' => $routeName,
                'message' => $this->sanitizer->sanitizeMessage($exception->getMessage()),
                'status' => ErrorLogStatus::PENDING,
                'entity_type' => null,
                'entity_id' => null,
                'route' => $routeName ?? $request?->path(),
                'exception_type' => $exception::class,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'context' => $this->sanitizer->sanitize([
                    'request' => [
                        'method' => $request?->method(),
                        'path' => $request?->path(),
                    ],
                ]),
                'occurred_at' => now(),
            ]);
        } catch (Throwable) {
            // Error recording must never replace the original application exception.
        }
    }

    private function shouldIgnore(Throwable $exception): bool
    {
        if ($exception instanceof ValidationException
            || $exception instanceof AuthenticationException
            || $exception instanceof AuthorizationException
            || $exception instanceof ModelNotFoundException) {
            return true;
        }

        return $exception instanceof HttpExceptionInterface
            && in_array($exception->getStatusCode(), [403, 404, 419], true);
    }

    private function moduleFromRoute(?string $routeName): string
    {
        if ($routeName === null || $routeName === '') {
            return 'system';
        }

        return str($routeName)->before('.')->limit(100)->toString();
    }
}
