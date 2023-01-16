<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Provider;

use PBaszak\MessengerCacheBundle\Contract\CacheTagProviderInterface;

class CacheTagProvider implements CacheTagProviderInterface
{
    public function createTag(string $group, ?string $groupOwnerId = null): string
    {
        return implode('_', array_filter(func_get_args()));
    }
}
