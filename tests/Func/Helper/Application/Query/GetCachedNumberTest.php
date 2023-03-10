<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group func */
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
