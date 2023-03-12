<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Invalidate
{
    /**
     * @param string[]    $tags                      you can declare tags to invalidate
     * @param bool        $useOwnerIdentifierForTags if `true` then `getOwnerIdentifier()` method is required and all
     *                                               tags will be prefixed with owner identifier
     * @param string[]    $groups                    define group of messages which You want to invalidate
     * @param bool        $useOwnerIdentifier        if `true` then `getOwnerIdentifier()` method is required and group
     *                                               will be prefixed with owner
     * @param string|null $adapter                   if `null` all TagAwareAdapters are used
     * @param bool        $invalidateBeforeDispatch  if `true` then cache is invalidated before dispatching message
     * @param bool        $invalidateOnException     if `true` then cache is invalidated even when exception is thrown
     * @param bool        $invalidateAsync           if `true` then cache is invalidated asynchronously
     */
    public function __construct(
        public readonly array $tags = [],
        public readonly bool $useOwnerIdentifierForTags = false,
        public readonly ?array $groups = null,
        public readonly bool $useOwnerIdentifier = true,
        public readonly ?string $adapter = null,
        public readonly bool $invalidateBeforeDispatch = false,
        public readonly bool $invalidateOnException = false,
        public readonly bool $invalidateAsync = false,
    ) {
    }
}
