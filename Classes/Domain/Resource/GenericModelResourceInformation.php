<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Resource;

use Neos\Flow\Http\Helper\UriHelper;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Resource\GenericModelResourceInformation as BaseGenericModelResourceInformation;
use Netlogix\JsonApiOrg\Schema\Relationships;

class GenericModelResourceInformation extends BaseGenericModelResourceInformation
{
    protected $pageSizes = [25, 50, 100];

    protected $priority = 100;

    public function getLinksForRelationship($payload, $relationshipName, $relationshipType = null): array
    {
        $result = parent::getLinksForRelationship($payload, $relationshipName, $relationshipType);
        $relationshipType = $relationshipType ?: $this->getResource($payload)->getRelationshipsToBeApiExposed()[$relationshipName];

        if ($relationshipType === Relationships::RELATIONSHIP_TYPE_COLLECTION) {
            unset($result['first']);
            foreach ($this->pageSizes as $pageSize) {
                try {
                    $result['first-' . $pageSize] = $this->getPaginationLinkForPageSize(
                        $pageSize,
                        $payload,
                        $relationshipName
                    );
                } catch (NoMatchingRouteException $e) {
                }
            }
        }

        return $result;
    }

    protected function getPaginationLinkForPageSize(int $pageSize, $payload, string $relationshipName): string
    {
        $uri = $this->getPublicRelatedUri($payload, $relationshipName);
        $arguments = UriHelper::parseQueryIntoArguments($uri);
        $arguments['page'] = [
            'size' => $pageSize,
            'number' => 0,
        ];
        return (string)UriHelper::uriWithArguments($uri, $arguments);
    }
}
