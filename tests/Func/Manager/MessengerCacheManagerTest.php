<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Manager;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[Cache(pool: 'runtime')]
class GetCachedStrings extends GetStrings implements Cacheable
{
}

/** @group func */
class MessengerCacheManagerTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
        self::getContainer()->get('messenger_cache.manager')->clear(pool: 'runtime');
    }

    /** @test */
    public function shouldNotReturnForceCacheRefreshStampFromCache(): void
    {
        $query = new GetCachedStrings();
        $firstResult = $this->messageBus->dispatch($query, [new ForceCacheRefreshStamp()])->last(HandledStamp::class)->getResult();

        $envelope = $this->messageBus->dispatch($query);
        $secondResult = $envelope->last(HandledStamp::class)->getResult();

        $this->assertEquals($firstResult, $secondResult);

        $stamps = $envelope->all(ForceCacheRefreshStamp::class);
        $this->assertEmpty($stamps);
    }

    /** @test */
    public function shouldReturnForceCacheRefreshStamp(): void
    {
        $query = new GetCachedStrings();
        $envelope = $this->messageBus->dispatch($query, [new ForceCacheRefreshStamp()]);

        $stamps = $envelope->all(ForceCacheRefreshStamp::class);
        $this->assertNotEmpty($stamps);
    }
}
