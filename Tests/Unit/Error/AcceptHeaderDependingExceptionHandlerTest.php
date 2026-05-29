<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Tests\Unit\Error;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Error\ExceptionHandlerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\ObjectAccess;
use Netlogix\WebApi\Error\AcceptHeaderDependingExceptionHandler;
use ReflectionMethod;
use RuntimeException;

class AcceptHeaderDependingExceptionHandlerTest extends UnitTestCase
{
    private const JSON_API_HANDLER_MOCK = 'AcceptHeaderTestJsonApiHandlerMock';
    private const JSON_HANDLER_MOCK     = 'AcceptHeaderTestJsonHandlerMock';
    private const FALLBACK_HANDLER_MOCK = 'AcceptHeaderTestFallbackHandlerMock';

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([self::JSON_API_HANDLER_MOCK, self::JSON_HANDLER_MOCK, self::FALLBACK_HANDLER_MOCK] as $mockClassName) {
            if (class_exists($mockClassName)) {
                continue;
            }
            $this->getMockBuilder(ExceptionHandlerInterface::class)
                ->setMockClassName($mockClassName)
                ->getMock();
        }
    }

    public static function provideAcceptHeaders(): iterable
    {
        return [
            'application/vnd.api+json picks the json-api handler' => [
                'application/vnd.api+json',
                self::JSON_API_HANDLER_MOCK,
            ],
            'application/json picks the json handler' => [
                'application/json',
                self::JSON_HANDLER_MOCK,
            ],
            'unconfigured media type falls back to *' => [
                'text/html',
                self::FALLBACK_HANDLER_MOCK,
            ],
            'q-values prefer the higher-weighted configured media type' => [
                'text/html;q=0.5, application/json;q=0.9',
                self::JSON_HANDLER_MOCK,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider provideAcceptHeaders
     */
    public function resolveHandlerConfigPicksHandlerByAcceptHeader(string $acceptHeader, string $expectedHandlerClass): void
    {
        $subject = new AcceptHeaderDependingExceptionHandler();
        ObjectAccess::setProperty($subject, 'availableErrorHandlers', [
            'application/vnd.api+json' => ['className' => self::JSON_API_HANDLER_MOCK],
            'application/json'         => ['className' => self::JSON_HANDLER_MOCK],
            '*'                        => ['className' => self::FALLBACK_HANDLER_MOCK],
        ], true);

        $request = (new ServerRequest('GET', '/'))->withHeader('Accept', $acceptHeader);
        $config = (new ReflectionMethod($subject, 'resolveHandlerConfig'))->invoke($subject, $request);

        $this->assertSame($expectedHandlerClass, $config['className']);
    }

    /**
     * @test
     */
    public function handleRethrowsWhenNoHandlerMatchesAndNoFallbackConfigured(): void
    {
        $subject = new AcceptHeaderDependingExceptionHandler();
        ObjectAccess::setProperty($subject, 'availableErrorHandlers', [
            'application/json' => ['className' => self::JSON_HANDLER_MOCK],
        ], true);

        $exception = new RuntimeException('boom');
        $request = (new ServerRequest('GET', '/'))->withHeader('Accept', 'text/html');

        $this->expectExceptionObject($exception);
        $subject->handle($request, $exception);
    }
}
