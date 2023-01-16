<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\DTO\Strings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Provider\StringProviderInterface;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetArrayOfStrings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetObjectOfStrings;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetStrings;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetStringsHandler
{
    public function __construct(
        private StringProviderInterface $stringProvider
    ) {
    }

    public function __invoke(GetStrings $query): array|object
    {
        $output = [];
        for ($i = 1; $i <= $query->numberOfStrings; $i++) {
            $output[] = $this->stringProvider->generateRandomString($query->stringLength);
        }

        switch (get_class($query)) {
            case GetArrayOfStrings::class:
                return $output;
            case GetObjectOfStrings::class:
                return (object) $output;
            default:
                return new Strings($output);
        }
    }
}
