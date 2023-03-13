<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Handler;

use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Message\InvalidateAsync;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles : InvalidateAsync::class)]
class InvalidateAsyncHandler
{
    public function __construct(
        private MessengerCacheManagerInterface $cacheManager
    ) {
    }

    public function __invoke(InvalidateAsync $message): void
    {
        $this->cacheManager->invalidate(
            $message->tags,
            $message->groups,
            $message->ownerIdentifier,
            $message->useOwnerIdentifierForTags,
            $message->pool
        );
    }
}
