<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Domain\QueryHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Provider\StringProviderInterface;
use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query\GetString;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetStringHandler
{
    public function __construct(
        private StringProviderInterface $stringProvider
    ) {}

    public function __invoke(GetString $query): string
    {
        return $this->stringProvider->generateRandomString($query->length);
    }
}
