<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract\Optional;

interface OwnerIdentifier
{
    /**
     * Returns the identifier of the cache owner, which will be used to create
     * a unique cache key for the selected owner - this can be a group of users.
     * example: `user_123` / `company_456` / `organization_321` / `813ffe00-1254-4f7f-943f-11a1b7619c9c`.
     */
    public function getOwnerIdentifier(): string;
}
