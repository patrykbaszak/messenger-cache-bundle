<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Handler;

use PBaszak\MessengerCacheBundle\Message\RefreshAsync;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler(handles : RefreshAsync::class)]
class RefreshAsyncHandler
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function __invoke(RefreshAsync $message): void
    {
        $this->messageBus->dispatch(
            $message->message,
            array_merge($message->stamps, [new ForceCacheRefreshStamp()])
        );
    }
}
