<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetObjectOfStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Cache(adapter: 'redis')]
class GetCachedObjectOfStrings extends GetObjectOfStrings implements Cacheable
{
}

/** @group integration */
class GetCachedObjectOfStringsTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnSameObjectOfStringsTwice(): void
    {
        $result = $this->handle(new GetCachedObjectOfStrings(20, 10));
        $result2 = $this->handle(new GetCachedObjectOfStrings(20, 10));

        self::assertEquals($result, $result2);
    }
}
