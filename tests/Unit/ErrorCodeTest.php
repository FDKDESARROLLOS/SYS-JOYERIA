<?php

namespace Tests\Unit;

use App\Models\ErrorCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\TestCase;

class ErrorCodeTest extends TestCase
{
    public function test_error_code_matches_its_model_configuration(): void
    {
        $errorCode = new ErrorCode;

        $this->assertContains(HasFactory::class, class_uses_recursive(ErrorCode::class));
        $this->assertNotContains(SoftDeletes::class, class_uses_recursive(ErrorCode::class));
        $this->assertSame([
            'code',
            'module',
            'title',
            'description',
            'severity',
            'status',
        ], $errorCode->getFillable());
        $this->assertSame('boolean', $errorCode->getCasts()['status']);
    }

    public function test_error_code_defines_its_logs_relationship(): void
    {
        $logs = (new ErrorCode)->logs();

        $this->assertInstanceOf(HasMany::class, $logs);
        $this->assertSame('error_code_id', $logs->getForeignKeyName());
    }
}
