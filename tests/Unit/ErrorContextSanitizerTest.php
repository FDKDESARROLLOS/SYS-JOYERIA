<?php

namespace Tests\Unit;

use App\Support\ErrorContextSanitizer;
use Tests\TestCase;

class ErrorContextSanitizerTest extends TestCase
{
    public function test_sensitive_context_values_are_redacted_recursively(): void
    {
        $context = app(ErrorContextSanitizer::class)->sanitize([
            'password' => 'secret',
            'nested' => [
                'Authorization' => 'Bearer token-value',
                'cookie' => 'session=value',
                'safe' => 'visible',
                '_token' => 'csrf-value',
            ],
            'access_token' => 'access-value',
        ]);

        $this->assertSame('[REDACTED]', $context['password']);
        $this->assertSame('[REDACTED]', $context['nested']['Authorization']);
        $this->assertSame('[REDACTED]', $context['nested']['cookie']);
        $this->assertSame('[REDACTED]', $context['nested']['_token']);
        $this->assertSame('[REDACTED]', $context['access_token']);
        $this->assertSame('visible', $context['nested']['safe']);
    }

    public function test_sensitive_values_in_messages_are_redacted_and_limited(): void
    {
        $sanitizer = app(ErrorContextSanitizer::class);
        $message = $sanitizer->sanitizeMessage('password=secret token=abc Authorization: Bearer xyz '.str_repeat('x', 2100));

        $this->assertStringContainsString('password=[REDACTED]', $message);
        $this->assertStringContainsString('token=[REDACTED]', $message);
        $this->assertStringContainsString('Bearer [REDACTED]', $message);
        $this->assertLessThanOrEqual(2001, mb_strlen($message));
    }
}
