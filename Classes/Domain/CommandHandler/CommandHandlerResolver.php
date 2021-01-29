<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\CommandHandler;

use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\WriteModelInterface;

interface CommandHandlerResolver
{
    public function getCommandHandlerForCommand(WriteModelInterface $command): ?CommandHandlerDelegation;
}
