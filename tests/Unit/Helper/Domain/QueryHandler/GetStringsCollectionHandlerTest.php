<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Unit\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO\Strings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO\StringsCollection;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStringsCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group unit */
class GetStringsCollectionHandlerTest extends KernelTestCase
{
    use HandleTrait;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
    }

    /** @test */
    public function shouldReturnStringsCollection(): void
    {
        $length = rand(1, 100);
        $numberOfStrings = rand(1, 10);
        $numberOfItems = rand(1, 10);

        $output = $this->handle(
            new GetStringsCollection($length, $numberOfStrings, $numberOfItems)
        );

        $this->assertInstanceOf(StringsCollection::class, $output);
        $this->assertEquals($numberOfItems, $output->count);

        foreach ($output->items as $item) {
            $this->assertInstanceOf(Strings::class, $item);
            $this->assertCount($numberOfStrings, $item->strings);

            foreach ($item->strings as $string) {
                $this->assertEquals($length, strlen($string));
            }
        }
    }
}
