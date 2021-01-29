<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Model;

interface SortParameter
{
    public function getField(): string;

    public function getDirection(): string;
}
