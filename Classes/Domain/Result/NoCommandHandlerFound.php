<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Result;

use Netlogix\WebApi\Domain\Command\Error;
use Netlogix\WebApi\Domain\Command\Errors;

class NoCommandHandlerFound implements Error
{
    public function getErrors(): Errors
    {
        return (new Errors())->addErrorMessage(
            'resource',
            'No CommandHandler was found'
        );
    }

}
