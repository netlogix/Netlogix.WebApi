<?php

namespace Netlogix\WebApi\Service;

use Netlogix\WebApi\Domain\Model\SortParameter;

interface SortParametersParser
{
    /**
     * @return SortParameter[]
     */
    public function parseQueryString(string $queryString): array;
}
