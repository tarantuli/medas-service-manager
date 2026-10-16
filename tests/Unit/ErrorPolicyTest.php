<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Unit;

use Medas\ServiceManager\ErrorHandling\{AggressiveErrorPolicy, BasicErrorPolicy};
use PHPUnit\Framework\TestCase;

final class ErrorPolicyTest extends TestCase
{
    private int $errorReporting;

    public function testTheAggressivePolicyThrowsOnAWarning(): void
    {
        new AggressiveErrorPolicy()->set();

        $this->expectException(\ErrorException::class);

        trigger_error('a warning', E_USER_WARNING);
    }

    /**
     * Regression: the handler ignored @, so code that suppresses a warning and handles the outcome itself (e.g.
     * exif_imagetype() on a file that isn't an image) got an ErrorException instead
     */
    public function testTheAggressivePolicyLeavesASuppressedWarningAlone(): void
    {
        new AggressiveErrorPolicy()->set();

        $result = @trigger_error('a suppressed warning', E_USER_WARNING);

        self::assertTrue($result);
    }

    public function testTheBasicPolicyLeavesAWarningToPhp(): void
    {
        new BasicErrorPolicy()->set();

        // Passed on to PHP's own handler, which the @ keeps quiet
        self::assertTrue(@trigger_error('a warning', E_USER_WARNING));
    }

    protected function setUp(): void
    {
        $this->errorReporting = error_reporting();
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        error_reporting($this->errorReporting);
    }
}
