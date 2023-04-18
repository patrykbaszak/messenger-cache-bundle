<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Manager;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags;
use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Provider\CacheKeyProvider;
use PBaszak\MessengerCacheBundle\Stamps\CacheItemHitStamp;
use PBaszak\MessengerCacheBundle\Stamps\CacheItemMissStamp;
use PBaszak\MessengerCacheBundle\Stamps\CacheItemTagsStamp;
use PBaszak\MessengerCacheBundle\Stamps\CacheRefreshTriggeredStamp;
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetString;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[Cache(pool: 'runtime')]
class GetCachedStrings extends GetStrings implements Cacheable
{
}

#[Cache(pool: 'runtime', refreshAfter: 1)]
class GetCachedString extends GetString implements Cacheable
{
}

#[Cache(pool: 'runtime')]
class GetCachedStringWithTag extends GetString implements Cacheable, DynamicTags
{
    public function getDynamicTags(): array
    {
        return ['tag'];
    }
}

/** @group func */
class MessengerCacheManagerTest extends KernelTestCase
{
    private MessageBusInterface $messageBus;
    private MessengerCacheManagerInterface $cacheManager;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
        $this->cacheManager = self::getContainer()->get('messenger_cache.manager');
        $this->cacheManager->clear(pool: 'runtime');
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

    /** @test */
    public function shouldAddTriggeredItemToCacheOnCacheRefresh(): void
    {
        $query = new GetCachedString();
        $cacheKey = (new CacheKeyProvider())->createKey($query);

        $this->messageBus->dispatch($query);
        sleep(2);
        $this->messageBus->dispatch($query);

        $item = $this->cacheManager->getPool('runtime')->getItem($cacheKey.'|triggered');

        $this->assertTrue($item->isHit());
    }

    /** @test */
    public function shouldAddTriggeredStampToOutputEnvelopeOnCacheRefresh(): void
    {
        $query = new GetCachedString();

        $result1 = $this->messageBus->dispatch($query);
        $this->assertEmpty($result1->all(CacheRefreshTriggeredStamp::class));
        sleep(2);
        $result2 = $this->messageBus->dispatch($query);
        $this->assertNotEmpty($result2->all(CacheRefreshTriggeredStamp::class));
    }

    /** @test */
    public function shouldNotTriggerCacheRefreshIfAlreadyTriggered(): void
    {
        $query = new GetCachedString();

        $this->messageBus->dispatch($query);
        sleep(2);
        /* trigger cache refresh */
        $this->messageBus->dispatch($query);
        $envelope = $this->messageBus->dispatch($query);
        $this->assertEmpty($envelope->all(CacheRefreshTriggeredStamp::class));
    }

    /** @test */
    public function shouldAddCacheItemMissStampToOutputEnvelopeOnCacheMiss(): void
    {
        $query = new GetCachedStrings();

        $envelope = $this->messageBus->dispatch($query);
        $this->assertNotEmpty($envelope->all(CacheItemMissStamp::class));
    }

    /** @test */
    public function shouldNotAddCacheItemHitStampToOutputEnvelopeOnCacheMiss(): void
    {
        $query = new GetCachedStrings();

        $envelope = $this->messageBus->dispatch($query);
        $this->assertEmpty($envelope->all(CacheItemHitStamp::class));
    }

    /** @test */
    public function shouldAddCacheItemHitStampToOutputEnvelopeOnCacheHit(): void
    {
        $query = new GetCachedStrings();

        $this->messageBus->dispatch($query);
        $envelope = $this->messageBus->dispatch($query);
        $this->assertNotEmpty($envelope->all(CacheItemHitStamp::class));
    }

    /** @test */
    public function shouldNotAddCacheItemMissStampToOutputEnvelopeOnCacheHit(): void
    {
        $query = new GetCachedStrings();

        $this->messageBus->dispatch($query);
        $envelope = $this->messageBus->dispatch($query);
        $this->assertEmpty($envelope->all(CacheItemMissStamp::class));
    }

    /** @test */
    public function shouldAddCacheItemTagsStampToOutputEnvelopeOnCacheMiss(): void
    {
        $query = new GetCachedStringWithTag();

        $envelope = $this->messageBus->dispatch($query);
        $this->assertNotEmpty($stamps = $envelope->all(CacheItemTagsStamp::class));
        $this->assertEquals(['tag'], $stamps[0]->tags);
    }

    /** @test */
    public function shouldAddCacheItemTagsStampToOutputEnvelopeOnCacheHit(): void
    {
        $query = new GetCachedStringWithTag();

        $this->messageBus->dispatch($query);
        $envelope = $this->messageBus->dispatch($query);
        $this->assertNotEmpty($stamps = $envelope->all(CacheItemTagsStamp::class));
        $this->assertEquals(['tag'], $stamps[0]->tags);
    }

    /** @test */
    public function shouldReturnOnlyOneCacheItemTagsStampOnForceRefreshCache(): void
    {
        $query = new GetCachedStringWithTag();

        $this->messageBus->dispatch($query);
        $envelope = $this->messageBus->dispatch($query, [new ForceCacheRefreshStamp()]);
        $this->assertCount(1, $stamps = $envelope->all(CacheItemTagsStamp::class));
        $this->assertEquals(['tag'], $stamps[0]->tags);
    }
}
