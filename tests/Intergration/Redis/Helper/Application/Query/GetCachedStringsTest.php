<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Cache(pool: 'redis')]
class GetCachedStrings extends GetStrings implements Cacheable
{
}

/** @group integration */
class GetCachedStringsTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnSameStringsTwice(): void
    {
        $result = $this->handle(new GetCachedStrings(20, 10));
        $result2 = $this->handle(new GetCachedStrings(20, 10));

        self::assertEquals($result, $result2);
    }
}
