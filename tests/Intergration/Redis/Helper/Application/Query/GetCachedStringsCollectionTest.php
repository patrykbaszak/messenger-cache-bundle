<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStringsCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Cache(adapter: 'redis')]
class GetCachedStringsCollection extends GetStringsCollection implements Cacheable
{
}

/** @group integration */
class GetCachedStringsCollectionTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnSameStringsCollectionTwice(): void
    {
        $result = $this->handle(new GetCachedStringsCollection(20, 10, 10));
        $result2 = $this->handle(new GetCachedStringsCollection(20, 10, 10));

        self::assertEquals($result, $result2);
    }
}
