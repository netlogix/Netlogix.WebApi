<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Service;

use Netlogix\WebApi\Domain\Model\DefaultSortParameter;
use Doctrine\Common\Collections\Criteria;

final class DefaultSortParametersParser implements SortParametersParser
{
    /**
     * {@inheritdoc}
     */
    public function parseQueryString(string $queryString): array
    {
        $sortParameters = [];
        $querySortFields = \explode(',', $queryString);
        foreach ($querySortFields as $querySortField) {
            $sortParameters[] = $this->parseQuerySortField($querySortField);
        }
        return $sortParameters;
    }

    private function parseQuerySortField(string $querySortField): DefaultSortParameter
    {
        $direction = (\substr(\trim($querySortField), 0, 1) === '-') ? Criteria::DESC : Criteria::ASC;
        $sortField = \trim($querySortField, '+- ');
        return new DefaultSortParameter($sortField, $direction);
    }
}
