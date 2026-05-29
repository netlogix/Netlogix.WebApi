<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Error;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Error\ExceptionHandlerInterface;
use Neos\Flow\Error\WithHttpStatusInterface;
use Neos\Flow\Error\WithReferenceCodeInterface;
use Neos\Flow\Http\Helper\ResponseInformationHelper;

/**
 * Flow exception handler that renders the throwable as a jsonapi.org
 * style error document. The shape mirrors the array produced by
 * Netlogix\JsonApiOrg\Controller\ApiController::errorAction() — a
 * top-level "errors" array of objects with "code" and "title", plus
 * (when available) an "id" carrying Flow's reference code.
 *
 * Implements Flow's ExceptionHandlerInterface so it can be used either as
 * the global Neos.Flow.error.exceptionHandler or — more interestingly —
 * invoked by AcceptHeaderDependingExceptionHandler, which captures the
 * emitted headers and body and turns them into a PSR-7 ResponseInterface.
 *
 * Exceptions implementing JsonSerializable contribute the body of one
 * error entry (so their jsonSerialize() should return the jsonapi.org
 * fields of a single error object — "code", "title", "detail", "meta",
 * …); the handler still wraps that entry in the standard envelope.
 *
 * @Flow\Scope("singleton")
 */
class JsonExceptionHandler implements ExceptionHandlerInterface
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

        echo json_encode([
            'errors' => [$error],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
