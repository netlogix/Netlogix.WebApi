<?php
declare(strict_types=1);

namespace Netlogix\WebApi\Domain\Service;

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context;

/**
 * @Flow\Scope("singleton")
 */
class AddAccountIdentifierToGlobalCacheIdentifierService implements CacheAwareInterface
{

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * Flow doesn't include the current account identifier into the global cache identifiers,
     * so we add it manually here.
     *
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        $account = $this->securityContext->getAccount();

        return sha1($account !== null ? $account->getAccountIdentifier() : 'no account');
    }

}
