<?php
declare(strict_types=1);

namespace Netlogix\WebApi\ViewHelper\Exception;

use Neos\FluidAdaptor\Core\ViewHelper\AbstractViewHelper;
use Throwable;

class JsonViewHelper extends AbstractViewHelper
{

    /**
     * @var boolean
     */
    protected $escapeChildren = false;

    public function initializeArguments()
    {
        $this->registerArgument('exception', Throwable::class, 'The exception', true);
        $this->registerArgument('statusCode', 'int', 'The status code', false, 500);
        $this->registerArgument('statusMessage', 'string', 'The status message', false, '');
        $this->registerArgument('referenceCode', 'string', 'The reference code', false, '');
    }

    public function render()
    {
        $exception = $this->arguments['exception'];
        if (!$exception instanceof Throwable) {
            throw new \InvalidArgumentException('$exception must be of type ' . Throwable::class, 1609856556);
        }

        return json_encode([
            'statusMessage' => $this->arguments['statusMessage'],
            'statusCode' => $this->arguments['statusCode'],
            'exceptionCode' => $exception->getCode(),
            'referenceCode' => $this->arguments['referenceCode'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

}
