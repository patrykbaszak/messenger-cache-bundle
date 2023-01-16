<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Contract\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\CacheKeyProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\CacheManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDecorator(MessageBusInterface::class)]
class MessageBusCacheDecorator implements MessageBusInterface
{
    public function __construct(
        private MessageBusInterface $decorated,
        private CacheKeyProviderInterface $cacheKeyProvider,
        private CacheManagerInterface $cacheManager,
    ) {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        return $message instanceof Cacheable
            ? $this->cacheManager->get(
                $message,
                $stamps,
                $this->cacheKeyProvider->createKey($message, $stamps),
                function () use ($message, $stamps): Envelope {
                    return $this->decorated->dispatch($message, $stamps);
                }
            )
            : $this->decorated->dispatch($message, $stamps);
    }
}