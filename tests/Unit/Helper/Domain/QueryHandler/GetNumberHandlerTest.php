<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetNumber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group unit */
class GetNumberHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
    }

    /** @test */
    public function shouldReturnNumber(): void
    {
        $output = $this->handle(
            new GetNumber()
        );

        $this->assertIsNumeric($output);
    }
}
