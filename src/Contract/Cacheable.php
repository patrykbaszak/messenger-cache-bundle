<?php

declare(strict_types=1);

namespace PBaszak\MessengerCacheBundle\Contract;

/**
 * @method Cacheable getHashableInstance() Returns a `$message` instance, a version that can 
 * be hashed to create a cache key. You can use this when the cache is owned by a group of 
 * users and at the same time there is a user instance in the object. The hash would be 
 * different for each user, so the cache would be individual. It's better if you return 
 * an object instance without user, then the cache will be shared within the group.
 * 
 * @method string getUniqueHash() Returns hash of given `$message` instance. You can use it
 * instead of `getHashableInstance()` and You have to use it when You want cache output from 
 * anonymous class instance.
 * 
 * @method string getOwnerIdentifier() Returns the identifier of the cache owner, which will 
 * be used to create a unique cache key for the selected owner - this can be a group of users.
 * example: `user_123` / `company_456` / `organization_321` / `813ffe00-1254-4f7f-943f-11a1b7619c9c`
 * 
 * @method int getDynamicTtl() The method is available only when the value of the `$ttl` 
 * argument in the Cache attribute is `null`.
 * 
 * @method bool isCacheable() Returns a dynamically generated `true`/`false` value and decides 
 * whether the request will be served using the cache.
 */
interface Cacheable
{
    
}
