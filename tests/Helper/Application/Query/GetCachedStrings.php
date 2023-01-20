<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Tests\Helper\Application\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;

#[Cache()]
class GetCachedStrings extends GetStrings
{
}
