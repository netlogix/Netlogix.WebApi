<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Model;

use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\Arguments;

/**
 * Interface to add additional meta properties to the top level result of collections
 * Must be implemented by the Selectable $result of the listAction
 *
 * Currently only works for collection results!
 */
interface TopLevelMetaAware
{
    public function getTopLevelMeta(?Arguments\Sorting $sort = null, ?Arguments\Filter $filter = null, ?Arguments\Page $page = null): array;
}
