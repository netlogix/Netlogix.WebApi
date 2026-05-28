<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Http\Middleware;

use Neos\Flow\Annotations as Flow;
use Netlogix\WebApi\Error\AcceptHeaderDependingExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Catches uncaught exceptions inside the PSR-15 middleware chain and turns
 * them into a ResponseInterface so that the surrounding CorsMiddleware
 * (and any other response-decorating middleware) sees an actual response
 * instead of a propagating exception.
 *
 * Positioned 'after session' via Settings.Middleware.yaml, which places it
 * inside any CORS wrapper configured 'before session' (e.g. Sitegeist.OffCORS),
 * so CORS headers reliably end up on error responses too, without requiring
 * that package to be installed.
 *
 * Logging is delegated entirely to the resolved Flow ExceptionHandler
 * (its `logException` rendering option drives ThrowableStorage); the
 * middleware itself does not log.
 */
class ExceptionToResponseMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var AcceptHeaderDependingExceptionHandler
     */
    protected $exceptionHandler;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            return $this->exceptionHandler->handle($request, $exception);
        }
    }
}
