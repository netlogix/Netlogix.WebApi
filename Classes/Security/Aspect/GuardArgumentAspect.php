<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Aspect;

use Netlogix\WebApi\Security\Annotations\GuardArgument;
use Netlogix\WebApi\Security\Authorization\Privilege\ArgumentGuard;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Exception\AccessDeniedException;
use Neos\Utility\PositionalArraySorter;

/**
 * @Flow\Scope("singleton")
 * @Flow\Aspect
 */
final class GuardArgumentAspect
{
    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ArgumentGuard[]
     */
    protected $argumentGuards;

    /**
     * @Flow\Before("methodAnnotatedWith(Netlogix\WebApi\Security\Annotations\GuardArgument)")
     * @param JoinPointInterface $joinPoint
     * @throws AccessDeniedException
     */
    public function blockMethodArgumentIfNotAllowed(JoinPointInterface $joinPoint): void
    {
        $this->initializeArgumentGuards();

        foreach ($this->getAnnotationsFor($joinPoint) as $annotation) {
            assert($annotation instanceof GuardArgument);
            $subject = $joinPoint->getMethodArgument($annotation->argumentName);
            $this->blockSubjectIfNecessary($subject);
        }
    }

    protected function initializeArgumentGuards(): void
    {
        if (is_array($this->argumentGuards)) {
            return;
        }

        $guards = [];
        foreach ($this->reflectionService->getAllImplementationClassNamesForInterface(ArgumentGuard::class) as $guardClassName) {
            $guard = $this->objectManager->get($guardClassName);
            assert($guard instanceof ArgumentGuard);
            $guards[$guardClassName] = $guard;
        }

        $sorter = new PositionalArraySorter($guards, 'position');
        $this->argumentGuards = $sorter->toArray();
    }

    protected function getAnnotationsFor(JoinPointInterface $joinPoint): array
    {
        return $this->reflectionService->getMethodAnnotations(
            $joinPoint->getClassName(),
            $joinPoint->getMethodName(),
            GuardArgument::class
        );
    }

    protected function blockSubjectIfNecessary($subject): void
    {
        if (!$subject || !is_object($subject)) {
            return;
        }

        foreach ($this->argumentGuards as $argumentGuard) {
            if ($argumentGuard->canHandle(get_class($subject)) && $argumentGuard->allow($subject)) {
                return;
            }
        }

        throw new AccessDeniedException('You are not allowed to access this subject.', 1562935593);
    }
}
