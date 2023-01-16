<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO\StringsCollection;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Provider\StringProviderInterface;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStringsCollection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler()]
class GetStringsCollectionHandler
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(GetStringsCollection $query): StringsCollection
    {
        $items = [];
        for ($i = 1; $i <= $query->numberOfItems; $i++) {
            $items[] = $this->handle(
                new GetStrings($query->stringLength, $query->numberOfStrings)
            );
        }

        return new StringsCollection($items);
    }
}
