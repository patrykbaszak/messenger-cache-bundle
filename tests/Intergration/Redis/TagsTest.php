<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Attribute\Invalidate;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Command\DoNothing;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetNumber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Invalidate(tags: ['test'])]
class DoTagsInvalidation extends DoNothing implements CacheInvalidation
{
}

#[Cache(pool: 'redis', tags: ['test'])]
class GetCachedNumberWithTags extends GetNumber implements Cacheable
{
}

/** @group integration */
class TagsTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
        self::getContainer()->get('messenger_cache.manager')->clear(pool: 'redis');
    }

    /** @test */
    public function shouldReturnSameResponseGetCachedNumber(): void
    {
        $result1 = $this->handle(new GetCachedNumberWithTags());
        $result2 = $this->handle(new GetCachedNumberWithTags());

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function shouldReturnNotSameResponseGetCachedNumber(): void
    {
        $result1 = $this->handle(new GetCachedNumberWithTags());
        $this->handle(new DoTagsInvalidation());
        $result2 = $this->handle(new GetCachedNumberWithTags());

        $this->assertNotEquals($result1, $result2);
    }
}
