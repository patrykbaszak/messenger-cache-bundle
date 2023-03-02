# Required interfaces #

Only one of the available interfaces in this catalogue is required.

If you use two then you will not achieve the expected result. It will be done flow returning the response from the cache. The invalidation will be skipped - always, even if you set the cache invalidation to take place before the actual action.

The above decision is due to the fact that if you were to allow both flows to be combined at the same time, this could result in the code being executed in a loop.

<br/>

## Cacheable ##

Enables a cache for the action being performed. Requires the addition of the `PBaszak\MessengerCacheBundle\Attribute\Cache` attribute. Inside the attribute you can define the cache parameters. In addition, you can use optional interfaces, for passing dynamic values.

## CacheInvalidation ##

Enables cache invalidation for the current flow. You decide on the invalidation settings in the required `PBaszak\MessengerCacheBundle\Attribute\Invalidate` or `PBaszak\MessengerCacheBundle\Attribute\Delete` attribute. You can use multiple `Invalidate` and `Delete` attributes with different settings in a single class implementing the `CacheInvalidation` interface.
