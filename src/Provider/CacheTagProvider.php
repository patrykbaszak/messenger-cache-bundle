<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheOwnerTagProviderInterface;

class CacheTagProvider implements MessengerCacheOwnerTagProviderInterface
{
    public function createGroupTag(string $group, ?string $groupId = null): string
    {
        return sprintf('%s_', implode('_', array_filter(func_get_args())));
    }

    public function createOwnerTag(string $ownerId): string
    {
        return sprintf('%s_', $ownerId);
    }
}
