<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group func */
class GetCachedStringTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnSameStringTwice(): void
    {
        $result = $this->handle(new GetCachedString(20));
        $result2 = $this->handle(new GetCachedString(20));

        self::assertEquals($result, $result2);
    }
}
