<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Invalidate
{
    /**
     * @param string[]    $tags                     you can declare tags to invalidate
     * @param string|null $pool                     if `null` all TagAwareAdapters are used
     * @param bool        $invalidateBeforeDispatch if `true` then cache is invalidated before dispatching message
     * @param bool        $invalidateOnException    if `true` then cache is invalidated even when exception is thrown
     * @param bool        $invalidateAsync          if `true` then cache is invalidated asynchronously
     */
    public function __construct(
        public readonly array $tags = [],
        public readonly ?string $pool = null,
        public readonly bool $invalidateBeforeDispatch = false,
        public readonly bool $invalidateOnException = false,
        public readonly bool $invalidateAsync = false,
    ) {
    }
}
