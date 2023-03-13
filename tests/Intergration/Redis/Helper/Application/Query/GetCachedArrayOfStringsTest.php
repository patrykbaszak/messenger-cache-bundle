<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Attribute\Invalidate;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Command\DoNothing;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetArrayOfStrings;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Cache(pool: 'redis')]
class GetCachedArrayOfStrings extends GetArrayOfStrings implements Cacheable, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

#[Invalidate(useOwnerIdentifier: true)]
class DoInvalidation extends DoNothing implements CacheInvalidation, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

/** @group integration */
class GetCachedArrayOfStringsTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnSameArrayOfStringsTwice(): void
    {
        $result = $this->handle(new GetCachedArrayOfStrings(20, 20));
        $result2 = $this->handle(new GetCachedArrayOfStrings(20, 20));

        self::assertEquals($result, $result2);
    }

    /** @test */
    public function shouldReturnDifferentArrayOfStringsAfterInvalidation(): void
    {
        $result = $this->handle(new GetCachedArrayOfStrings(20, 20));
        $this->handle(new DoInvalidation());
        $result2 = $this->handle(new GetCachedArrayOfStrings(20, 20));

        self::assertNotEquals($result, $result2);
    }
}
