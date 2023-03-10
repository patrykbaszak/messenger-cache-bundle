<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Integration\Redis\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Optional\OwnerIdentifier;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetNumber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

#[Cache(adapter: 'redis')]
class GetCachedNumber extends GetNumber implements Cacheable, OwnerIdentifier
{
    public function getOwnerIdentifier(): string
    {
        return 'company_1634566';
    }
}

/** @group integration */
class GetCachedNumberTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnSameNumberTwice(): void
    {
        $result = $this->handle(new GetCachedNumber());
        $result2 = $this->handle(new GetCachedNumber());

        self::assertEquals($result, $result2);
    }
}
