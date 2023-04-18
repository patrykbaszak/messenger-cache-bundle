<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Decorator;

use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Event\CacheEvent;
use PBaszak\MessengerCacheBundle\Event\CacheInvalidationEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class MessageBusCacheEventsDecorator implements MessageBusInterface
{
    public function __construct(
        private MessageBusInterface $decorated,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param StampInterface[] $stamps
     */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $envelope = $this->decorated->dispatch($message, $stamps);

        if ($message instanceof Cacheable) {
            $this->eventDispatcher->dispatch(new CacheEvent($envelope, $stamps));
        } elseif ($message instanceof CacheInvalidation) {
            $this->eventDispatcher->dispatch(new CacheInvalidationEvent($envelope, $stamps));
        }

        return $envelope;
    }
}
