<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Authorization\Privilege;

use Netlogix\WebApi\Domain\Command\AuthenticatedUserCommand;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security;

class AuthenticatedUserCommandWhitelist implements ArgumentGuard
{
    /**
     * @Flow\Inject
     * @var Security\Context
     */
    protected $securityContext;

    public function canHandle(string $entityName): bool
    {
        return is_a($entityName, AuthenticatedUserCommand::class, true);
    }

    public function allow($entity): bool
    {
        return (bool)$this->securityContext->getAccount();
    }

    /**
     * @return string|int
     */
    public function getPosition()
    {
        return 'end';
    }
}
