<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Command;

use Exception;
use JsonSerializable;
use Neos\Error\Messages\Message;

final class Errors implements JsonSerializable
{
    private $issues = [];

    public function addErrorMessage(string $source, string $detail, array $meta = [], ?int $code = null): self
    {
        $this->issues[] = array_filter([
            'code' => $code,
            'detail' => $detail,
            'source' => $source,
            'meta' => $meta
        ]);
        return $this;
    }

    public function addErrorFromMessage(string $source, Message $message): self
    {
        $this->issues[] = array_filter([
            'code' => $message->getCode(),
            'detail' => $message->render(),
            'source' => $source,
            'meta' => array_filter(
                array_map(
                    function ($argument) {
                        try {
                            return (string)$argument;
                        } catch (Exception $e) {
                        }
                    },
                    $message->getArguments()
                )
            )
        ]);
        return $this;
    }

    public function addRawErrorArray(array ...$errors): self
    {
        foreach ($errors as $error) {
            $this->issues[] = array_filter([
                'code' => $error['code'] ?? null,
                'detail' => $error['detail'] ?? null,
                'source' => $error['source'] ?? null,
                'meta' => $error['meta'] ?? null,
            ]);
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->issues;
    }

    public function getIssues(): array
    {
        return $this->issues;
    }
}
