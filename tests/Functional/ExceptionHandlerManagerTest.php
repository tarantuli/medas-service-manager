<?php

declare(strict_types=1);

namespace Medas\ServiceManagerTest\Functional;

use Medas\ServiceManager\ServiceConfig;
use Medas\ServiceManagerTest\MockUps\{
    CapturingExceptionHandlerManager,
    RecordingExceptionHandler,
    ThrowingExceptionHandler
};

final class ExceptionHandlerManagerTest extends BaseTestClass
{
    public function testAThrowingHandlerDoesNotStopTheOthers(): void
    {
        $manager = $this->managerWithHandlers(
            ThrowingExceptionHandler::class,
            RecordingExceptionHandler::class
        );

        $exception = new \LogicException('the original error');

        $manager->handle($exception);

        self::assertSame(
            [$exception],
            $this->serviceManager()->resolve(RecordingExceptionHandler::class)->received
        );

        // Only the failing handler is reported: the original exception was handled by the other one
        self::assertCount(1, $manager->written);

        self::assertStringStartsWith(
            '[Exception handler failed] ' . ThrowingExceptionHandler::class . ': RuntimeException',
            $manager->written[0]
        );

        self::assertStringContainsString('the handler itself failed', $manager->written[0]);
    }

    public function testTheOriginalExceptionIsReportedWhenNoHandlerHandlesIt(): void
    {
        $manager = $this->managerWithHandlers(ThrowingExceptionHandler::class);

        $manager->handle(new \LogicException('the original error'));

        self::assertCount(2, $manager->written);
        self::assertStringStartsWith('[Exception handler failed]', $manager->written[0]);
        self::assertStringStartsWith('[Unhandled exception] LogicException', $manager->written[1]);
        self::assertStringContainsString('the original error', $manager->written[1]);
    }

    private function managerWithHandlers(string ...$handlerClasses): CapturingExceptionHandlerManager
    {
        $serviceManager = $this->serviceManager();
        $config = $serviceManager->config;
        $manager = new CapturingExceptionHandlerManager();

        $manager->prepareHandlers($serviceManager, new ServiceConfig(
            objectInstantiatorClass: $config->objectInstantiatorClass,
            errorPolicy: $config->errorPolicy,
            isDev: $config->isDev,
            mapping: $config->mapping,
            packageClasses: $config->packageClasses,
            manualBindings: $config->manualBindings,
            parameterResolvers: $config->parameterResolvers,
            argumentProcessors: $config->argumentProcessors,
            exceptionHandlers: $handlerClasses,
        ));

        return $manager;
    }

    protected function setUp(): void
    {
        $this->serviceManager()->resolve(RecordingExceptionHandler::class)->received = [];
    }

    protected function tearDown(): void
    {
        // The constructor installs itself as the global exception handler
        restore_exception_handler();
    }
}
