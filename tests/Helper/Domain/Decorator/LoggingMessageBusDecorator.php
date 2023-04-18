<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class LoggingMessageBusDecorator implements MessageBusInterface
{
    /** @param MessageBusInterface $decorated is public for easy access from test */
    public function __construct(public MessageBusInterface $decorated)
    {
    }

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        return $this->decorated->dispatch($message, $stamps);
    }
}
