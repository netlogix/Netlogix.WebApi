<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Command;

use Neos\Error\Messages\Result;

class ValidationBasedError implements Error
{
    protected $source;

    protected $result;

    public function __construct(string $source, Result $result)
    {
        $this->source = $source;
        $this->result = $result;
    }

    public function getValidationResult(): Result
    {
        return $this->result;
    }

    public function getErrors(): Errors
    {
        $result = new Errors();
        foreach ($this->getValidationResult()->getFlattenedErrors() as $key => $messages) {
            $source = join('/', array_filter([
                $this->source,
                $key
            ]));
            foreach ($messages as $message) {
                $result->addErrorFromMessage($source, $message);
            }
        }
        return $result;
    }
}
