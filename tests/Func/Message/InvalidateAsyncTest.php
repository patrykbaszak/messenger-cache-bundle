<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Func\Message;

use PBaszak\MessengerCacheBundle\Handler\InvalidateAsyncHandler;
use PBaszak\MessengerCacheBundle\Message\InvalidateAsync;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

/** @group func */
class InvalidateAsyncTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldHandleInvalidateAsyncMessage(): void
    {
        $envelope = $this->messageBus->dispatch(
            new InvalidateAsync(['test'], 'runtime')
        );

        $handledStamps = $envelope->all(HandledStamp::class);
        self::assertCount(1, $handledStamps);
        /** @var HandledStamp $handledStamp */
        $handledStamp = $handledStamps[0];
        self::assertSame(InvalidateAsyncHandler::class.'::__invoke', $handledStamp->getHandlerName());
    }
}
