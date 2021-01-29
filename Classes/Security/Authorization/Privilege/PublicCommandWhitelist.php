<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Authorization\Privilege;

use Netlogix\WebApi\Domain\Command\PublicCommand;

class PublicCommandWhitelist implements ArgumentGuard
{

    public function canHandle(string $entityName): bool
    {
        return is_a($entityName, PublicCommand::class, true);
    }

    public function allow($entity): bool
    {
        return true;
    }

    /**
     * @return string|int
     */
    public function getPosition()
    {
        return 'end';
    }
}
