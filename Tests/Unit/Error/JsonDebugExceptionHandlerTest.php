<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Tests\Unit\Error;

use Neos\Flow\Error\AbstractExceptionHandler;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\WebApi\Error\JsonDebugExceptionHandler;
use RuntimeException;

/**
 * Counterpart to JsonExceptionHandlerTest for the debug handler: the same
 * bugfix (extend AbstractExceptionHandler so exceptions get persistently
 * logged) applies here, and echoExceptionWeb() additionally exposes the
 * developer-facing detail/meta fields.
 */
class JsonDebugExceptionHandlerTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        // AbstractExceptionHandler::__construct() registers the instance via
        // set_exception_handler(); undo that so handlers don't leak across tests.
        restore_exception_handler();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function handlerExtendsAbstractExceptionHandler(): void
    {
        self::assertInstanceOf(AbstractExceptionHandler::class, new JsonDebugExceptionHandler());
    }

    /**
     * @test
     */
    public function handlerInheritsTheThrowableLoggingCollaborators(): void
    {
        // AcceptHeaderDependingExceptionHandler wires the persistent logging by
        // calling the inherited injectThrowableStorage()/injectLogger() setters
        // (guarded with method_exists()). Extending AbstractExceptionHandler is
        // what provides them, so their presence guards the fix.
        $handler = new JsonDebugExceptionHandler();
        self::assertTrue(method_exists($handler, 'injectThrowableStorage'));
        self::assertTrue(method_exists($handler, 'injectLogger'));
    }

    /**
     * @test
     */
    public function echoExceptionWebExposesDebugDetailAndMeta(): void
    {
        $handler = new JsonDebugExceptionHandler();
        $exception = new RuntimeException('something exploded', 4711);

        $body = $this->captureOutput(static fn () => $handler->echoExceptionWeb($exception));

        $error = json_decode($body, true)['errors'][0];
        self::assertSame(4711, $error['code']);
        self::assertSame('something exploded', $error['detail']);
        self::assertSame(RuntimeException::class, $error['meta']['exceptionType']);
        self::assertSame($exception->getFile(), $error['meta']['file']);
        self::assertSame($exception->getLine(), $error['meta']['line']);
        self::assertIsArray($error['meta']['trace']);
    }

    /**
     * @test
     */
    public function echoExceptionWebSerializesThePreviousExceptionChain(): void
    {
        $root = new RuntimeException('root cause', 17);
        $exception = new RuntimeException('outer failure', 99, $root);

        $handler = new JsonDebugExceptionHandler();
        $body = $this->captureOutput(static fn () => $handler->echoExceptionWeb($exception));

        $error = json_decode($body, true)['errors'][0];
        self::assertArrayHasKey('previous', $error['meta']);
        self::assertSame(17, $error['meta']['previous']['code']);
        self::assertSame('root cause', $error['meta']['previous']['detail']);
        self::assertSame(RuntimeException::class, $error['meta']['previous']['title']);
    }

    private function captureOutput(callable $callback): string
    {
        ob_start();
        try {
            $callback();
        } finally {
            $body = ob_get_clean();
        }
        return $body;
    }
}
