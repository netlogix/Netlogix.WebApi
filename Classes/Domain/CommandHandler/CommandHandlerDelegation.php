<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\CommandHandler;

use Netlogix\WebApi\Domain\Command\Result;
use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\WriteModelInterface;

final class CommandHandlerDelegation
{
    private $commandHandlerObject;

    private $commandHandlerMethodName;

    private $command;

    public function __construct($commandHandlerObject, string $commandHandlerMethodName, WriteModelInterface $command)
    {
        $this->commandHandlerObject = $commandHandlerObject;
        $this->commandHandlerMethodName = $commandHandlerMethodName;
        $this->command = $command;
    }

    public function handle(): Result
    {
        $handler = [$this->commandHandlerObject, $this->commandHandlerMethodName];
        return $handler($this->command);
    }

    public function getCommandHandlerObject()
    {
        return $this->commandHandlerObject;
    }

    public function getCommandHandlerMethodName()
    {
        return $this->commandHandlerMethodName;
    }
}
