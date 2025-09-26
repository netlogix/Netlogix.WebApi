<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Command;

use Netlogix\JsonApiOrg\AnnotationGenerics\Annotations as JsonApi;
use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\ReadModelInterface;

#[JsonApi\ExposeType(typeName: 'success')]
final class Success implements Result, ReadModelInterface
{
}
