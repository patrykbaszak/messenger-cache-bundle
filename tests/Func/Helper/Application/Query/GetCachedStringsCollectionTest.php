<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/** @group func */
class GetCachedStringsCollectionTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get(MessageBusInterface::class);
    }

    /** @test */
    public function shouldReturnSameStringsCollectionTwice(): void
    {
        $result = $this->handle(new GetCachedStringsCollection(20, 10, 10));
        $result2 = $this->handle(new GetCachedStringsCollection(20, 10, 10));

        self::assertEquals($result, $result2);
    }
}
