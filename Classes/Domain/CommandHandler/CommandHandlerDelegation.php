<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\CommandHandler;

use Netlogix\WebApi\Domain\Command\Error;
use Netlogix\WebApi\Domain\Command\Result;
use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\WriteModelInterface;

final class CommandHandlerDelegation
{
    private object $commandHandlerObject;

    private string $commandHandlerMethodName;

    private WriteModelInterface $command;

    private string $commandValidatorMethodName;

    public function __construct(
        object $commandHandlerObject,
        string $commandHandlerMethodName,
        WriteModelInterface $command,
        string $commandValidatorMethodName = ''
    ) {
        $this->commandHandlerObject = $commandHandlerObject;
        $this->commandHandlerMethodName = $commandHandlerMethodName;
        $this->command = $command;
        $this->commandValidatorMethodName = $commandValidatorMethodName;
    }

    public function handle(): Result
    {
        $handler = [$this->commandHandlerObject, $this->commandHandlerMethodName];
        return $handler($this->command);
    }

    public function validate(): Result
    {
        $validator = [$this->commandHandlerObject, $this->commandValidatorMethodName];
        return $validator($this->command);
    }

    /**
     * Validates the command if a custom validator is defined.
     * If the validation fails, the Error is returned.
     * Otherwise, the handle method is executed and its result is returned.
     *
     * @return Result The result of the command execution, or an Error if validation fails.
     */
    public function validateAndHandle(): Result
    {
        if ($this->hasCommandValidatorMethod()) {
            $validationResult = $this->validate();
            if ($validationResult instanceof Error) {
                return $validationResult;
            }
        }

        return $this->handle();
    }

    public function getCommandHandlerObject(): object
    {
        return $this->commandHandlerObject;
    }

    public function getCommandHandlerMethodName(): string
    {
        return $this->commandHandlerMethodName;
    }

    public function getCommandValidatorMethodName(): string
    {
        return $this->commandValidatorMethodName;
    }

    public function hasCommandValidatorMethod(): bool
    {
        return $this->commandValidatorMethodName !== '';
    }
}
