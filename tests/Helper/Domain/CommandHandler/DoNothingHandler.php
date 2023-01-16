<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Domain\CommandHandler;

use PBaszak\MessengerCacheBundle\Tests\Helper\Application\Command\DoNothing;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class DoNothingHandler
{
    public function __invoke(DoNothing $command): void
    {
        // do nothing
    }
}
