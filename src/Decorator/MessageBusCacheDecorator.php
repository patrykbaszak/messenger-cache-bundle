<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheKeyProviderInterface;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDecorator('messenger.bus.default')]
class MessageBusCacheDecorator implements MessageBusInterface
{
    public function __construct(
        private MessageBusInterface $decorated,
        private MessengerCacheKeyProviderInterface $cacheKeyProvider,
        private MessengerCacheManagerInterface $cacheManager,
    ) {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if ($message instanceof Cacheable) {
            return $this->dispatchCacheableMessage($message, $stamps);
        } elseif ($message instanceof CacheInvalidation) {
            return $this->dispatchCacheInvalidationMessage($message, $stamps);
        }
        return $this->decorated->dispatch($message, $stamps);
    }

    private function dispatchCacheableMessage(object $message, array $stamps): Envelope
    {
        return $this->cacheManager->get(
            $message,
            $stamps,
            $this->cacheKeyProvider->createKey($message, $stamps),
            function () use ($message, $stamps): Envelope {
                return $this->decorated->dispatch($message, $stamps);
            }
        );
    }

    private function dispatchCacheInvalidationMessage(object $message, array $stamps): Envelope
    {
        // $this->cacheManager->invalidate($message, $stamps);

        return $this->decorated->dispatch($message, $stamps);
    }
}
