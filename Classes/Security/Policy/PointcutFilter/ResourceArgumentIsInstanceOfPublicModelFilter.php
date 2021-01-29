<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Policy\PointcutFilter;

use Netlogix\WebApi\Controller\GenericModelController;
use Netlogix\WebApi\Domain\Model\PublicModel;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ResourceArgumentIsInstanceOfPublicModelFilter implements PointcutFilterInterface
{
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        return $className === GenericModelController::class && $methodName === 'showAction';
    }

    public function hasRuntimeEvaluationsDefinition()
    {
        return true;
    }

    public function getRuntimeEvaluationsDefinition()
    {
        return [
            'methodArgumentConstraints' => [
                'resource' => [
                    'operator' => ['instanceof'],
                    'value' => ['\\' . PublicModel::class]
                ]
            ]
        ];
    }

    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
        return $classNameIndex;
    }

}
