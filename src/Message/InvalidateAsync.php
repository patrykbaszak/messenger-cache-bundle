<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Message;

class InvalidateAsync
{
    /**
     * @param string[]    $tags                      You can declare tags to invalidate. Works with `ownerIdentifier` if `useOwnerIdentifierForTags` is `true`.
     * @param string[]    $groups                    Define group of messages which You want to invalidate. Works with `ownerIdentifier`.
     * @param string|null $ownerIdentifier           if `null` then `getOwnerIdentifier()` method is required
     * @param bool        $useOwnerIdentifierForTags if `true` then `getOwnerIdentifier()` method is required and all tags will be prefixed with owner identifier
     * @param string|null $adapter                   if `null` all TagAwareAdapters are used
     */
    public function __construct(
        public readonly array $tags = [],
        public readonly array $groups = [],
        public readonly ?string $ownerIdentifier = null,
        public readonly bool $useOwnerIdentifierForTags = false,
        public readonly ?string $adapter = null
    ) {
    }
}
