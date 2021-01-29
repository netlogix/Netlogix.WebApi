<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Security\Authorization\Privilege;

interface ArgumentGuard
{
    public function canHandle(string $entityName): bool;

    public function allow($entity): bool;

    /**
     * @return string|int
     */
    public function getPosition();
}
