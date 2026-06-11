<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Tests\Unit\Error;

use Neos\Flow\Error\AbstractExceptionHandler;
use Neos\Flow\Error\WithHttpStatusInterface;
use Neos\Flow\Error\WithReferenceCodeInterface;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\WebApi\Error\JsonExceptionHandler;
use RuntimeException;

/**
 * Covers the bugfix that lets JsonExceptionHandler extend
 * AbstractExceptionHandler so handled exceptions are persistently logged
 * (throwableStorage + logger), while echoExceptionWeb() keeps producing the
 * jsonapi.org error envelope.
 *
 * The logging itself lives in the inherited, framework-owned handleException()
 * and is therefore asserted structurally (class hierarchy + inherited
 * collaborator setters) rather than by driving handleException(), whose control
 * flow under the CLI SAPI differs between Neos.Flow patch releases.
 */
class JsonExceptionHandlerTest extends UnitTestCase
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
        // This is the heart of the fix: only by extending AbstractExceptionHandler
        // does the handler inherit handleException() with its logging behaviour.
        self::assertInstanceOf(AbstractExceptionHandler::class, new JsonExceptionHandler());
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
        $handler = new JsonExceptionHandler();
        self::assertTrue(method_exists($handler, 'injectThrowableStorage'));
        self::assertTrue(method_exists($handler, 'injectLogger'));
    }

    /**
     * @test
     */
    public function echoExceptionWebRendersJsonApiErrorEnvelopeForPlainThrowable(): void
    {
        $handler = new JsonExceptionHandler();

        $body = $this->captureOutput(static fn () => $handler->echoExceptionWeb(new RuntimeException('boom', 4711)));

        $decoded = json_decode($body, true);
        self::assertSame(4711, $decoded['errors'][0]['code']);
        self::assertSame('Internal Server Error', $decoded['errors'][0]['title']);
        // The non-debug handler must not leak technical details onto the wire.
        self::assertArrayNotHasKey('detail', $decoded['errors'][0]);
        self::assertArrayNotHasKey('meta', $decoded['errors'][0]);
    }

    /**
     * @test
     */
    public function echoExceptionWebHonoursHttpStatusAndReferenceCode(): void
    {
        $exception = new class('nope') extends RuntimeException implements WithHttpStatusInterface, WithReferenceCodeInterface {
            public function getStatusCode()
            {
                return 404;
            }

            public function getReferenceCode()
            {
                return '20260611001122abcdef';
            }
        };

        $handler = new JsonExceptionHandler();
        $body = $this->captureOutput(static fn () => $handler->echoExceptionWeb($exception));

        $decoded = json_decode($body, true);
        self::assertSame('Not Found', $decoded['errors'][0]['title']);
        self::assertSame('20260611001122abcdef', $decoded['errors'][0]['id']);
    }

    /**
     * @test
     */
    public function echoExceptionWebRendersJsonSerializableExceptionAsErrorEntry(): void
    {
        $exception = new class('serialized') extends RuntimeException implements \JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['code' => 'custom-code', 'title' => 'Custom title'];
            }
        };

        $handler = new JsonExceptionHandler();
        $body = $this->captureOutput(static fn () => $handler->echoExceptionWeb($exception));

        $decoded = json_decode($body, true);
        self::assertSame('custom-code', $decoded['errors'][0]['code']);
        self::assertSame('Custom title', $decoded['errors'][0]['title']);
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