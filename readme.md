# Messenger Cache Bundle #

Hi there! Let me introduce you to the Messenger Cache Bundle! This package will help you overcome any issues you may have with handling cache.

However, let's start with what this package won't do for you:

- It won't define adapter services for you. Every application is different and this will look slightly different in each one. The package only needs the name of the defined service from the services.yaml file, which you'll need to provide.
- For now, it won't handle cache using any clever method that you can push into any part of the code. Currently, it's only supported by hooking into the dispatch() method of the MessageBusInterface object.
So, what will this package do for you?

- You know what? It's better to show you!
    ```yaml
    # config/packages/messenger_cache.yaml
    messenger_cache:
        adapters:
            default: 'Symfony\Component\Cache\Adapter\RedisTagAwareAdapter' # You should use TagAware adapters.
            fastest: 'Symfony\Component\Cache\Adapter\PhpArrayAdapter'
            runtime: 'Symfony\Component\Cache\Adapter\ArrayAdapter'
    
        # This two boolean parameters are add decorators for cache manager.
        runtime_cache_storage: false # cache invalidation is not ready here, if You want to use invalidation type `false`
        cacheable_callback_support: true # you should use it, but if You NEVER will use `isCacheable()` method in Your $message then You can type `false` for optimalization
    ```

    ```php
    # src/Application/Query/GetRandomString.php
    use PBaszak\MessengerCacheBundle\Attribute\Cache;
    use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

    #[Cache(adapter: 'runtime')]
    class GetRandomString implements Cacheable
    {
    }
    ```
    And that's it! Now, if you call the above query from anywhere in the code using the MessageBus object, the response will be stored in memory within the currently executed request.

    You define the adapters yourself and only the default adapter is required - the package will use it every time you don't define the adapter argument in the Cache attribute. So in the above example, it would be Redis!

    ```php
    # src/Domain/Manager/StringsManager.php
    use App\Application\Query\GetRandomString;
    use Symfony\Component\Messenger\MessageBusInterface;
    use Symfony\Component\Messenger\HandleTrait;

    class StringsManager
    {
        use HandleTrait;

        public function __construct(MessageBusInterface $messageBus)
        {
            $this->messageBus = $messageBus;
        }

        public function getAlwaysSameRandomString(): string
        {
            return $this->handle(
                new GetRandomString()
            );
        }
    }

    $stringsManager = new StringsManager();
    $result0 = $stringsManager->getAlwaysSameRandomString();
    $result1 = $stringsManager->getAlwaysSameRandomString();

    var_dump($result0 === $result1); // true
    ```

<hr>

## Function description ##

### **Attributes** ###
- **Cache** - this attribute allows you to define cache operation options.
- **Invalidate** - this attribute allows you to define invalidation options. You can use multiple `Invalidate` attributes for one `$message` class.

### **Contracts** ###

### Required ###

You can add only one of the two. Adding two will result in `Cacheable` being the only one that works.

- **Cacheable** - an interface that you must implement in the `$message` class to start using `cache`. It requires adding the `Cache` attribute.

- **CacheInvalidation** - an interface that you must implement in the `$message` class to start using cache invalidation. It requires adding the `Invalidate` attribute.

### Optional ###

Optional interfaces modify the way the cache is handled. They allow, for example, dynamically providing arguments for cache or invalidation settings.

- **CacheableCallback** - requires the `cacheable_callback_support: true` parameter. Allows you to define the `isCacheable(): bool` method, which can conditionally disable cache.

- **DynamicTags** - you can define dynamic tags.

- **DynamicTtl** - you can define a dynamic `ttl` value.

- **HashableInstance** - use this when the selected cache is for multiple users, but you have the user context inside the `$message` object. Return an instance of the `$message` object in the method, which will be stripped of user context.

- **OwnerIdentifier** - use this if the cache should be dedicated to a user or group of users and to make it invalidatable.

- **UniqueHash** - instead of `HashableInstance`, you can define how the object will be hashed yourself. If you don't use either `UniqueHash` or `HashableInstance`, the object will be hashed in the default way.

### Replaceable ###

Interfaces allow you to hook into the cache handling process. You can replace original classes with your custom ones.

- **MessengerCacheKeyProviderInterface** - returns the cache key.
- **MessengerCacheOwnerTagProviderInterface** - returns the group or owner tag.
- **MessengerCacheManagerInterface** - a manager that performs the connection logic with the Symfony Cache. If you want to use a different cache provider or write your own, substitute the `MessengerCacheManager` class using this interface.
