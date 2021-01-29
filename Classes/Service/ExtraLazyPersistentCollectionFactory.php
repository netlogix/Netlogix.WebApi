<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Annotations as Flow;
use Netlogix\JsonApiOrg\AnnotationGenerics\Doctrine\ExtraLazyPersistentCollection;

/**
 * TODO: Move to Netlogix.JsonApi.AnnotationGenerics?
 *
 * @Flow\Scope("singleton")
 */
class ExtraLazyPersistentCollectionFactory
{

    /**
     * @Flow\Inject
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function createForEntityClassName(string $entityClassName): ExtraLazyPersistentCollection
    {
        return ExtraLazyPersistentCollection::createFromEntityPersister(
            $this
                ->entityManager
                ->getUnitOfWork()
                ->getEntityPersister($entityClassName)
        );
    }

    public function createForEntityClassNameWithFilter(
        string $entityClassName,
        string $fieldName,
        $value
    ): ExtraLazyPersistentCollection {
        return $this->createForEntityClassName($entityClassName)
            ->matching(
                Criteria::create()->where(
                    Criteria::expr()->eq($fieldName, $value)
                )
            );
    }

}
