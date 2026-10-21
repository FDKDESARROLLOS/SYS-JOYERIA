<?php

namespace Tests\Unit;

use App\Models\ErrorLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ErrorLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_log_matches_its_model_configuration(): void
    {
        $errorLog = new ErrorLog;

        $this->assertContains(HasFactory::class, class_uses_recursive(ErrorLog::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(ErrorLog::class));
        $this->assertSame([
            'error_code_id',
            'user_id',
            'source',
            'module',
            'action',
            'message',
            'status',
            'entity_type',
            'entity_id',
            'route',
            'exception_type',
            'file',
            'line',
            'context',
            'occurred_at',
        ], $errorLog->getFillable());
        $this->assertSame('integer', $errorLog->getCasts()['entity_id']);
        $this->assertSame('integer', $errorLog->getCasts()['line']);
        $this->assertSame('array', $errorLog->getCasts()['context']);
        $this->assertSame('datetime', $errorLog->getCasts()['occurred_at']);
        $this->assertFalse(method_exists(ErrorLog::class, 'entity'));
    }

    public function test_error_log_defines_its_relationships(): void
    {
        $errorLog = new ErrorLog;

        $errorCode = $errorLog->errorCode();
        $user = $errorLog->user();

        $this->assertInstanceOf(BelongsTo::class, $errorCode);
        $this->assertSame('error_code_id', $errorCode->getForeignKeyName());

        $this->assertInstanceOf(BelongsTo::class, $user);
        $this->assertSame('user_id', $user->getForeignKeyName());
    }

    public function test_error_code_id_and_user_id_are_nullable_in_the_database(): void
    {
        $columns = collect(Schema::getColumns('error_logs'))
            ->keyBy('name');

        $this->assertTrue($columns['error_code_id']['nullable']);
        $this->assertTrue($columns['user_id']['nullable']);
    }
}
