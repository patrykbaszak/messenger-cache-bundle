<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Stamps;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

class ForceCacheRefreshStamp implements NonSendableStampInterface
{
}
