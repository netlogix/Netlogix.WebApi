<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Command;

interface Error extends Result
{
    public function getErrors(): Errors;
}
