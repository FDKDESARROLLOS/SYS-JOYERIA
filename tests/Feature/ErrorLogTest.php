<?php

namespace Tests\Feature;

use App\Models\ErrorCode;
use App\Models\ErrorLog;
use App\Models\User;
use App\Services\ErrorLogRecorder;
use App\Support\ErrorLogStatus;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ErrorLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_real_error_logs_without_demo_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $errorLog = $this->createErrorLog(['message' => 'Fallo persistido real.']);

        $response = $this->actingAs($admin)->get(route('settings.errors.logs'));

        $response
            ->assertOk()
            ->assertViewIs('settings.error-logs')
            ->assertSee($errorLog->message)
            ->assertDontSee('PROD-002')
            ->assertViewHas('errorLogs', fn ($logs): bool => $logs->contains('id', $errorLog->id));
    }

    public function test_admin_and_technical_users_can_access_logs_but_other_roles_cannot(): void
    {
        foreach (['admin', 'technical'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('settings.errors.logs'))->assertOk();
        }

        $support = User::factory()->create(['role' => 'support']);

        $this->actingAs($support)->get(route('settings.errors.logs'))->assertForbidden();
        $this->actingAs($support)
            ->patch(route('settings.errors.logs.status', $this->createErrorLog()))
            ->assertForbidden();
    }

    public function test_log_filters_search_related_code_fields_source_status_and_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Lucía Técnica']);
        $errorCode = ErrorCode::create([
            'code' => 'REAL-LOG-001',
            'module' => 'Inventario',
            'title' => 'Fallo de inventario',
            'severity' => 'error',
            'status' => true,
        ]);
        $target = $this->createErrorLog([
            'error_code_id' => $errorCode->id,
            'user_id' => $admin->id,
            'source' => 'backend',
            'module' => 'Inventario',
            'action' => 'inventory.store',
            'message' => 'Fallo filtrable.',
            'status' => ErrorLogStatus::REVIEWED,
            'occurred_at' => '2026-10-12 10:00:00',
        ]);
        $this->createErrorLog([
            'source' => 'system',
            'module' => 'Sistema',
            'status' => ErrorLogStatus::PENDING,
            'occurred_at' => '2026-10-10 10:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('settings.errors.logs', [
            'search' => 'REAL-LOG-001',
            'module' => 'Inventario',
            'source' => 'backend',
            'status' => ErrorLogStatus::REVIEWED,
            'date_from' => '2026-10-11',
            'date_to' => '2026-10-13',
        ]));

        $response->assertViewHas('errorLogs', function ($logs) use ($target): bool {
            return $logs->total() === 1 && $logs->first()->id === $target->id;
        });
    }

    public function test_log_listing_paginates_ten_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 11) as $number) {
            $this->createErrorLog([
                'message' => "Error {$number}",
                'occurred_at' => now()->subMinutes($number),
            ]);
        }

        $response = $this->actingAs($admin)->get(route('settings.errors.logs'));

        $response->assertViewHas('errorLogs', function ($logs): bool {
            return $logs->total() === 11 && $logs->perPage() === 10 && $logs->count() === 10;
        });
    }

    public function test_relevant_exception_is_captured_centrally_with_authenticated_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Route::middleware('auth')->get('/test-error-log', function (): never {
            throw new RuntimeException('Relevant application failure');
        })->name('testing.error-log');

        $this->actingAs($admin)->get('/test-error-log')->assertStatus(500);

        $this->assertDatabaseHas('error_logs', [
            'user_id' => $admin->id,
            'source' => 'backend',
            'module' => 'testing',
            'action' => 'testing.error-log',
            'message' => 'Relevant application failure',
            'status' => ErrorLogStatus::PENDING,
            'route' => 'testing.error-log',
            'exception_type' => RuntimeException::class,
        ]);

        $capturedLog = ErrorLog::query()->latest('id')->firstOrFail();

        $this->assertNotNull($capturedLog->occurred_at);
        $this->assertSame(__FILE__, $capturedLog->file);
        $this->assertGreaterThan(0, $capturedLog->line);
    }

    public function test_expected_exceptions_are_not_captured(): void
    {
        $recorder = app(ErrorLogRecorder::class);
        $request = Request::create('/expected-error', 'GET');

        $recorder->record(ValidationException::withMessages(['field' => 'Invalid']), $request);
        $recorder->record(new AuthorizationException, $request);
        $recorder->record(new NotFoundHttpException, $request);

        $this->assertDatabaseCount('error_logs', 0);
    }

    public function test_error_context_and_message_are_sanitized_before_persistence(): void
    {
        $request = Request::create('/sensitive-error', 'POST');
        $request->setUserResolver(fn (): User => User::factory()->create(['role' => 'admin']));

        app(ErrorLogRecorder::class)->record(
            new RuntimeException('password=secret token=token-value'),
            $request,
        );

        $errorLog = ErrorLog::query()->latest('id')->firstOrFail();

        $this->assertSame('password=[REDACTED] token=[REDACTED]', $errorLog->message);
        $this->assertSame('POST', $errorLog->context['request']['method']);
        $this->assertArrayNotHasKey('password', $errorLog->context);
    }

    public function test_error_status_transitions_are_persisted_and_invalid_transitions_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $errorLog = $this->createErrorLog(['status' => ErrorLogStatus::PENDING]);

        $this->actingAs($admin)
            ->patch(route('settings.errors.logs.status', $errorLog), ['status' => ErrorLogStatus::REVIEWED])
            ->assertRedirect(route('settings.errors.logs'));

        $errorLog->refresh();
        $this->assertSame(ErrorLogStatus::REVIEWED, $errorLog->status);

        $this->actingAs($admin)
            ->patch(route('settings.errors.logs.status', $errorLog), ['status' => ErrorLogStatus::RESOLVED])
            ->assertRedirect(route('settings.errors.logs'));

        $errorLog->refresh();
        $this->assertSame(ErrorLogStatus::RESOLVED, $errorLog->status);

        $this->actingAs($admin)
            ->patch(route('settings.errors.logs.status', $errorLog), ['status' => ErrorLogStatus::IGNORED])
            ->assertSessionHasErrors('status');

        $ignoredLog = $this->createErrorLog(['status' => ErrorLogStatus::PENDING]);

        $this->actingAs($admin)
            ->patch(route('settings.errors.logs.status', $ignoredLog), ['status' => ErrorLogStatus::IGNORED])
            ->assertRedirect(route('settings.errors.logs'));

        $ignoredLog->refresh();
        $this->assertSame(ErrorLogStatus::IGNORED, $ignoredLog->status);

        $this->actingAs($admin)
            ->patch(route('settings.errors.logs.status', $ignoredLog), ['status' => ErrorLogStatus::REVIEWED])
            ->assertRedirect(route('settings.errors.logs'));

        $this->assertSame(ErrorLogStatus::REVIEWED, $ignoredLog->refresh()->status);
    }

    public function test_log_output_escapes_untrusted_values(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->createErrorLog([
            'message' => '<script>alert("message")</script>',
            'exception_type' => '<script>alert("exception")</script>',
            'file' => '<script>alert("file")</script>',
            'route' => '<script>alert("route")</script>',
        ]);

        $response = $this->actingAs($admin)->get(route('settings.errors.logs'));

        $response
            ->assertOk()
            ->assertDontSee('<script>alert("message")</script>', false)
            ->assertDontSee('<script>alert("exception")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createErrorLog(array $attributes = []): ErrorLog
    {
        return ErrorLog::create(array_merge([
            'source' => 'backend',
            'module' => 'system',
            'action' => 'testing.action',
            'message' => 'Error de prueba',
            'status' => ErrorLogStatus::PENDING,
            'exception_type' => RuntimeException::class,
            'file' => __FILE__,
            'line' => 1,
            'context' => ['safe' => 'value'],
            'occurred_at' => now(),
        ], $attributes));
    }
}
