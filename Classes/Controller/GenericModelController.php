<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Controller;

use Doctrine\Common\Collections\Selectable;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Netlogix\JsonApiOrg\AnnotationGenerics\Controller\GenericModelController as BaseGenericModelController;
use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\Arguments as RequestArgument;
use Netlogix\JsonApiOrg\AnnotationGenerics\Domain\Model\WriteModelInterface;
use Netlogix\WebApi\Domain\Command\Error;
use Netlogix\WebApi\Domain\CommandHandler\CommandHandlerDelegation;
use Netlogix\WebApi\Domain\CommandHandler\CommandHandlerResolver;
use Netlogix\WebApi\Domain\Result\NoCommandHandlerFound;
use Netlogix\WebApi\Security\Annotations as Security;

class GenericModelController extends BaseGenericModelController
{

    /**
     * @var CommandHandlerResolver[]
     */
    private $commandHandlerResolvers;

    public function __construct(ReflectionService $reflectionService, ObjectManagerInterface $objectManager)
    {
        $this->commandHandlerResolvers = array_map(function (string $className) use ($objectManager) {
            return $objectManager->get($className);
        }, $reflectionService->getAllImplementationClassNamesForInterface(CommandHandlerResolver::class));
    }

    /**
     * @param WriteModelInterface $resource
     * @param string $resourceType
     * @Flow\MapRequestBody("resource")
     * @Security\GuardArgument("resource")
     */
    public function createAction(WriteModelInterface $resource, $resourceType = '')
    {
        if (class_exists('Tideways\Profiler')) {
            \Tideways\Profiler::setTransactionName(get_class($resource));
        }
        $delegation = $this->resolveCommandHandlerDelegation($resource);
        if ($delegation === null) {
            return $this->respondWithError(new NoCommandHandlerFound());
        }

        if (class_exists('Tideways\Profiler')) {
            \Tideways\Profiler::setTransactionName(
                sprintf('%s::%s', get_class($delegation->getCommandHandlerObject()), $delegation->getCommandHandlerMethodName())
            );
        }
        if ($delegation->getCommandValidatorMethodName() !== '') {
            $validationResult = $delegation->validate();
            if ($validationResult instanceof Error) {
                return $this->respondWithError($validationResult);
            }
        }
        $result = $delegation->handle();
        if ($result instanceof Error) {
            return $this->respondWithError($result);
        }
        $topLevel = $this->relationshipIterator->createTopLevel($result);
        $this->view->assign('value', $topLevel);
    }

    protected function createTopLevelOfCollection(
        Selectable $result,
        RequestArgument\Sorting $sort = null,
        RequestArgument\Filter $filter = null,
        RequestArgument\Page $page = null
    ) {

        if ($sort) {
            $result = $result->matching($sort->getCriteria());
        }
        assert($result instanceof Selectable);

        if ($filter) {
            $result = $result->matching($filter->getCriteria());
        }
        assert($result instanceof Selectable);

        if ($page) {
            $limitedResult = $result->matching($page->getCriteria());
        } else {
            $limitedResult = $result;
        }
        assert($limitedResult instanceof Selectable);

        $topLevel = $this->relationshipIterator->createTopLevel($limitedResult);
        $topLevel = $this->applyPaginationMetaToTopLevel($topLevel, count($result), count($limitedResult), $page);

        return $topLevel;
    }

    protected function mapErrorResult($status, $result): array
    {
        list($status, $result) = parent::mapErrorResult($status, $result);
        array_walk($result['errors'], function (&$error) use (&$status) {
            if ($error['code'] === 1221560910) {
                $error['code'] = 404;
                $error['title'] = 'Object not found.';
                $status = max($status, 404);
            }
        });
        return [$status, $result];
    }

    protected function respondWithError(Error $error): string
    {
        $this->response->setContentType(current($this->supportedMediaTypes));
        $this->response->setStatusCode(400);

        return json_encode(
            [
                'errors' => $error->getErrors()
            ],
            JSON_PRETTY_PRINT
        );
    }

    private function resolveCommandHandlerDelegation(WriteModelInterface $command): ?CommandHandlerDelegation
    {
        foreach ($this->commandHandlerResolvers as $commandHandlerResolver) {
            $delegation = $commandHandlerResolver->getCommandHandlerForCommand($command);

            if ($delegation) {
                return $delegation;
            }
        }

        return null;
    }
}
