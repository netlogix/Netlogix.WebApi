<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Error;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\ExceptionHandlerInterface;
use Neos\Flow\Error\WithHttpStatusInterface;
use Neos\Flow\Http\Helper\MediaTypeHelper;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Log\ThrowableStorageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Picks one of Flow's ExceptionHandlerInterface implementations based on
 * the request's "Accept" header, invokes it, and adapts its (echo + header)
 * style output into a PSR-7 ResponseInterface for the middleware chain.
 *
 * Configuration lives at Netlogix.WebApi.error.availableErrorHandlers
 * and follows the same shape Flow uses for its own Neos.Flow.error.exceptionHandler:
 *
 *   className: 'Fully\Qualified\HandlerClass'
 *   options:
 *     defaultRenderingOptions: { … }
 *     renderingGroups: { … }
 *
 * The special key "*" is the fallback for any non-matching Accept header.
 * If no entry matches and "*" is not configured, the exception is re-thrown,
 * leaving the standard set_exception_handler chain in charge.
 *
 * @Flow\Scope("singleton")
 */
class AcceptHeaderDependingExceptionHandler
{
    /**
     * @Flow\InjectConfiguration(path="error.availableErrorHandlers", package="Netlogix.WebApi")
     * @var array<string, array{className: string, options?: array}>
     */
    protected array $availableErrorHandlers = [];

    /**
     * @Flow\Inject
     * @var ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @Flow\Inject
     * @var PsrLoggerFactoryInterface
     */
    protected $loggerFactory;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    public function handle(ServerRequestInterface $request, Throwable $exception): ResponseInterface
    {
        $handlerConfig = $this->resolveHandlerConfig($request);
        if ($handlerConfig === null) {
            // No handler configured for this Accept value (and no "*" fallback) —
            // let the exception escape so Flow's set_exception_handler can run.
            throw $exception;
        }

        $handler = $this->instantiateHandler($handlerConfig);

        $headersBefore = headers_list();
        ob_start();
        header_remove();
        try {
            $handler->handleException($exception);
        } finally {
            $body = ob_get_clean();
            $newHeaderLines = headers_list();
            header_remove();
            $this->restoreHeaderLines($headersBefore);
        }

        return $this->buildResponse($exception, $body, $newHeaderLines);
    }

    /**
     * Restores queued PHP header lines exactly as captured by headers_list().
     *
     * @param array<int, string> $headerLines
     */
    private function restoreHeaderLines(array $headerLines): void
    {
        foreach ($headerLines as $index => $headerLine) {
            header($headerLine, $index === 0);
        }
    }

    /**
     * @return array{className: string, options?: array}|null
     */
    private function resolveHandlerConfig(ServerRequestInterface $request): ?array
    {
        $supportedMediaTypes = array_values(array_filter(
            array_keys($this->availableErrorHandlers),
            static fn (string $mediaType): bool => $mediaType !== '*'
        ));

        $acceptedMediaTypes = MediaTypeHelper::determineAcceptedMediaTypes($request);
        $acceptedMediaTypes = array_filter(
            $acceptedMediaTypes,
            fn(string $candidate) => match(trim($candidate)) {
            '*/*' => false,
            '*' => false,
            default => true,
        });
        $negotiatedMediaType = MediaTypeHelper::negotiateMediaType($acceptedMediaTypes, $supportedMediaTypes);

        if ($negotiatedMediaType !== null && isset($this->availableErrorHandlers[$negotiatedMediaType])) {
            return $this->availableErrorHandlers[$negotiatedMediaType];
        }

        return $this->availableErrorHandlers['*'] ?? null;
    }

    /**
     * @param array{className: string, options?: array} $config
     */
    private function instantiateHandler(array $config): ExceptionHandlerInterface
    {
        $className = $config['className'] ?? '';
        if (!is_string($className) || !class_exists($className)) {
            throw new \RuntimeException(
                sprintf('Configured exception handler class "%s" does not exist', $className),
                1748434000
            );
        }

        // Mirror Neos\Flow\Core\Booting\Scripts::initializeErrorHandling so
        // Flow's stock handlers get the dependencies they expect.
        $handler = new $className();
        if (!($handler instanceof ExceptionHandlerInterface)) {
            throw new \RuntimeException(
                sprintf('Configured exception handler "%s" must implement %s', $className, ExceptionHandlerInterface::class),
                1748434001
            );
        }

        if (method_exists($handler, 'injectLogger')) {
            /** @var LoggerInterface $systemLogger */
            $systemLogger = $this->loggerFactory->get('systemLogger');
            $handler->injectLogger($systemLogger);
        }
        if (method_exists($handler, 'injectThrowableStorage')) {
            $handler->injectThrowableStorage($this->throwableStorage);
        }

        $handler->setOptions($config['options'] ?? []);

        return $handler;
    }

    /**
     * @param list<string> $newHeaderLines
     */
    private function buildResponse(Throwable $exception, string $body, array $newHeaderLines): ResponseInterface
    {
        $statusCode = $exception instanceof WithHttpStatusInterface ? $exception->getStatusCode() : 500;
        $response = $this->responseFactory->createResponse($statusCode);

        foreach ($newHeaderLines as $headerLine) {
            $colonPosition = strpos($headerLine, ':');
            if ($colonPosition === false) {
                continue;
            }
            $name = substr($headerLine, 0, $colonPosition);
            $value = ltrim(substr($headerLine, $colonPosition + 1));
            $response = $response->withAddedHeader($name, $value);
        }

        $responseBody = $response->getBody();
        $responseBody->write($body);
        // Flow's RequestHandler::sendResponse() reads from the current
        // stream position without rewinding, so the body would otherwise
        // arrive as Content-Length: 0.
        $responseBody->rewind();

        return $response;
    }

    /**
     * @param list<string> $headerLines
     * @return list<string>
     */
    private function collectHeaderNames(array $headerLines): array
    {
        $names = [];
        foreach ($headerLines as $headerLine) {
            $colonPosition = strpos($headerLine, ':');
            if ($colonPosition === false) {
                continue;
            }
            $name = substr($headerLine, 0, $colonPosition);
            if (!in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        return $names;
    }
}
