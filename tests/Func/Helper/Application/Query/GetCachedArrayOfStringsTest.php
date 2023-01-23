<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/** @group func */
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
}
