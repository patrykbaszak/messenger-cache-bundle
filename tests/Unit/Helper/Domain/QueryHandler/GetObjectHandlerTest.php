<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group unit */
class GetObjectHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
    }

    /** @test */
    public function shouldReturnEmptyObject(): void
    {
        $output = $this->handle(
            new GetObject()
        );

        $this->assertIsObject($output);
        $this->assertEquals((object) [], $output);
    }
}
