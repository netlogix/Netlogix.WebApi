<?php

namespace Netlogix\WebApi\Exception;

use Neos\Flow\Exception;

class BadRequestException extends Exception
{
    protected $statusCode = 400;
}
