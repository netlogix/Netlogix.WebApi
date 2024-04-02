<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Policy\PointcutFilter;

use Netlogix\WebApi\Controller\GenericModelController;
use Netlogix\WebApi\Domain\Command\PublicCommand;
use Neos\Flow\Aop\Builder\ClassNameIndex;
use Neos\Flow\Aop\Pointcut\PointcutFilterInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class ResourceArgumentIsInstanceOfPublicCommandFilter implements PointcutFilterInterface
{
    public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier)
    {
        return is_a($className, GenericModelController::class, true) && $methodName === 'createAction';
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
                    'value' => ['\\' . PublicCommand::class]
                ]
            ]
        ];
    }

    public function reduceTargetClassNames(ClassNameIndex $classNameIndex)
    {
        return $classNameIndex;
    }

}
