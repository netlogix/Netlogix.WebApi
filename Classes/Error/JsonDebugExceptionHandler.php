<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Error;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\ExceptionHandlerInterface;
use Neos\Flow\Error\WithHttpStatusInterface;
use Neos\Flow\Error\WithReferenceCodeInterface;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Throwable;

/**
 * JSON counterpart to Neos\Flow\Error\DebugExceptionHandler. Uses the
 * same jsonapi.org-style envelope as JsonExceptionHandler (mirroring
 * Netlogix\JsonApiOrg\Controller\ApiController::errorAction()) and
 * additionally publishes the technical details a developer needs while
 * debugging:
 *
 *   - "detail": $exception->getMessage()
 *   - "meta.exceptionType", "meta.file", "meta.line", "meta.trace"
 *   - "meta.previous": recursive chain of preceding exceptions
 *
 * Reserved for non-production contexts. Wire it up through the
 * Development override at Netlogix.WebApi.error.availableErrorHandlers,
 * mirroring how Flow's Development context replaces the global
 * ProductionExceptionHandler with the DebugExceptionHandler.
 *
 * Exceptions implementing JsonSerializable contribute the body of one
 * error entry — same contract as JsonExceptionHandler; the additional
 * debug fields only apply to plain throwables.
 *
 * @Flow\Scope("singleton")
 */
class JsonDebugExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @var array
     */
    protected $options = [];

    public function setOptions(array $options)
    {
        unset($options['className']);
        $this->options = $options;
    }

    /**
     * @param \Throwable $exception
     */
    public function handleException($exception)
    {
        if (error_reporting() === 0) {
            return;
        }

        $statusCode = $exception instanceof WithHttpStatusInterface ? $exception->getStatusCode() : 500;
        $statusMessage = ResponseInformationHelper::getStatusMessageByCode($statusCode);

        if (!headers_sent()) {
            header(sprintf('HTTP/1.1 %d %s', $statusCode, $statusMessage));
        }
        header('Content-Type: application/json; charset=utf-8');

        if ($exception instanceof \JsonSerializable) {
            echo json_encode([
                'errors' => [$exception],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return;
        }

        $error = [];
        if ($exception instanceof WithReferenceCodeInterface && $exception->getReferenceCode() !== '') {
            $error['id'] = $exception->getReferenceCode();
        }
        $error['code'] = $exception->getCode();
        $error['title'] = $statusMessage;
        $error['detail'] = $exception->getMessage();
        $error['meta'] = [
            'exceptionType' => get_class($exception),
            'file'          => $exception->getFile(),
            'line'          => $exception->getLine(),
            'trace'         => $this->formatTrace($exception->getTrace()),
        ];
        if ($exception->getPrevious() !== null) {
            $error['meta']['previous'] = $this->serializePrevious($exception->getPrevious());
        }

        echo json_encode([
            'errors' => [$error],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array<int, array<string, mixed>> $trace
     * @return list<array{file: ?string, line: ?int, class: ?string, type: ?string, function: ?string}>
     */
    private function formatTrace(array $trace): array
    {
        // Frame "args" are deliberately omitted: they may contain
        // closures, resources or sensitive runtime values that either
        // break json_encode or leak information not intended for the
        // wire.
        return array_map(static fn (array $frame): array => [
            'file'     => $frame['file'] ?? null,
            'line'     => $frame['line'] ?? null,
            'class'    => $frame['class'] ?? null,
            'type'     => $frame['type'] ?? null,
            'function' => $frame['function'] ?? null,
        ], $trace);
    }

    /**
     * Recursive helper rendering a previous exception in the same
     * error-object shape as the outer entry. The "title" carries the
     * exception class (no HTTP reason phrase applies to a nested cause).
     *
     * @return array{code: int, title: string, detail: string, meta: array<string, mixed>}|null
     */
    private function serializePrevious(?Throwable $previous): ?array
    {
        if ($previous === null) {
            return null;
        }
        $entry = [
            'code'   => $previous->getCode(),
            'title'  => get_class($previous),
            'detail' => $previous->getMessage(),
            'meta'   => [
                'file'  => $previous->getFile(),
                'line'  => $previous->getLine(),
                'trace' => $this->formatTrace($previous->getTrace()),
            ],
        ];
        if ($previous->getPrevious() !== null) {
            $entry['meta']['previous'] = $this->serializePrevious($previous->getPrevious());
        }
        return $entry;
    }
}
