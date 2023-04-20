<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Func\Message;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Handler\RefreshAsyncHandler;
use PBaszak\MessengerCacheBundle\Message\RefreshAsync;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[Cache(pool: 'runtime')]
class GetCachedStrings extends GetStrings implements Cacheable
{
}

/** @group func */
class RefreshAsyncTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldHandleRefreshAsyncMessage(): void
    {
        $envelope = $this->messageBus->dispatch(
            new RefreshAsync(new GetCachedStrings())
        );

        $handledStamps = $envelope->all(HandledStamp::class);
        self::assertCount(1, $handledStamps);
        /** @var HandledStamp $handledStamp */
        $handledStamp = $handledStamps[0];
        self::assertSame(RefreshAsyncHandler::class.'::__invoke', $handledStamp->getHandlerName());
    }
}
