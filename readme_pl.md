# Messenger Cache Bundle #

## Kompatybilność ##
- **PHP 8.0** - **PHP 8.2**
- **Symfony 5.4** - **Symfony 6.2**
 
## Instalacja ##

```sh
composer require pbaszak/symfony-messenger-cache-bundle
```
W pliku `config/bundles.php`
```php
<?php

return [
    // ...
    PBaszak\MessengerCacheBundle\MessengerCacheBundle::class => ['all' => true],
];
```

## Szybki start ##
### **Krok 0** ### 
Po zainstalowaniu paczki w pierwszej kolejności upewnij się, że w pliku `config/packages/messenger.yaml` masz zdefiniowany `default_bus`, jeśli nie masz, to Symfony zwróci Ci błąd **lub nie** co też jest problemem, jeśli nie wszystkie wstrzyknięcia `MessageBusInterface $messageBus` w Twojej aplikacji mają być udekorowane przez `MessageBusCacheDecorator`.
W większości przypadków powinno to wyglądać następująco:
```yaml
framework:
    messenger:
        default_bus: messenger.bus.default
        buses:
            messenger.bus.default:
```
Zauważ, że nie ma tutaj deklaracji `cachedMessage.bus`, ta została już zadeklarowana przez niniejszy bundle i możesz z niej skorzystać zamieniając nazwę argumentu konstruktora z `MessageBusInterface $messageBus` na `MessageBusInterface $cachedMessageBus`.
### **Krok 1** ### 
Przy pierwszej kompilacji Symfony może wystąpić błąd mówiący, że nie masz zdefiniowanego domyślnego `cache pool`, który to możesz zdefiniować w tablicy `messenger_cache.pools` lub `framework.cache.pools`. Ta tablica odpowiada za listę adapterów cache, które będzie obsługiwać `MessengerCacheManager`. Aby ją poprawnie zadeklarować zaczniemy od odwiedzenia pliku `config/packages/cache.yaml`, w którym znajdziesz definicje `cache pools`. Domyślnym `pool` jest ten o nazwie `app` w przypadku definicji w `framework.cache.pools` lub `default` w przypadku definicji aliasów w `messenger_cache.pools`. Poniżej przykład pliku z pliku `config/packages/cache.yaml`:
```yaml
framework:
    cache:
        pools:
            app: # domyślnie używany pool przez bundle to ten o nazwie `app`
                adapter: cache.adapter.redis_tag_aware
                tags: true
            runtime: 
                adapter: cache.adapter.array
                tags: true
            filesystem:
                adapter: cache.adapter.filesystem
```
Nie ma obowiązku używania adapterów wspierających tagi, jeśli nie będziesz używał inwalidacji cache. Niemniej nawet wtedy rekomenduję używanie adapterów wspierających cache.
Pliku `config/packages/messenger_cache.yaml` nie masz w swoim projekcie i w ramach "szybkiego startu" nie potrzebujesz go mieć. Ale poniżej w tym pliku readme znajdziesz informacje jak taki plik powinien wyglądać i jakie ma możliwości konfiguracji.

### Krok 2 ###
Zmodyfikuj swoją klasę typu Message, której odpowiedź chcesz cache'ować, zgodnie z poniższym przykładem. Uwaga, wybrałem bardziej skomplikowany przykład, aby pokazać Ci jak związać cache z użytkownikiem, w taki sposób, aby była możliwość inwalidacji tego cache, co wydaję mi się będzie najczęstszym przypadkiem użycia:

```php
# src/Application/User/Query/GetUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Cache; # obowiązkowy atrybut
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags; # opcjonalny interfejs
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable; # obowiązkowy interfejs

#[Cache(ttl: 3600)]
class GetUserConfig implements Cacheable, DynamicTags
{
    public function __construct(public readonly string $userId) {}

    public function getDynamicTags(): array
    {
        return ['user_' . $this->userId];
    }
}
```

### Krok 3 ###
Zmodyfikuj konstruktor klasy, w której wykonujesz `$this->messageBus->dispatch(new GetUserConfig($userId))` lub `$this->handle(new GetUserConfig($userId))`.

Przed modyfikacją:
```php
class UserConfigController extends AbstractController
{
    public function __construct(MessageBusInterface $messageBus) {}
}
```

Po modyfikacji:
```php
class UserConfigController extends AbstractController
{
    public function __construct(MessageBusInterface $cachedMessageBus) {}
}
```
**GOTOWE**.<br>Teraz, jeśli wywołujesz `GetUserConfig()` w klasie `UserConfigController` to odpowiedź będzie cache'owana w domyślnym `cache pool`.

### **Krok dodatkowy** (inwalidacja) ###
```php
# src/Application/User/Command/UpdateUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Invalidate; # obowiązkowy atrybut
use PBaszak\MessengerCacheBundle\Contract\Optional\DynamicTags; # opcjonalny interfejs
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation; # obowiązkowy interfejs

#[Invalidate()]
class UpdatetUserConfig implements CacheInvalidation, DynamicTags
{
    public function __construct(
        public readonly string $usesId,
        public readonly array $config,
    ) {}

    public function getDynamicTags(): array
    {
        return ['user_' . $this->userId];
    }
}
```
Jak widzisz metoda `getDynamicTags` nie uległa zmianie, dlatego bardzo mocno rekomenduję umieszczanie tej metody w Traitach.

### **Krok dodatkowy** (usuwanie kontekstu użytkownika, np. w przypadku cache dla grupy użytkowników) ###

```php
# src/Application/User/Query/GetUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Cache; # obowiązkowy atrybut
use PBaszak\MessengerCacheBundle\Contract\Optional\HashableInstance; # opcjonalny interfejs
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable; # obowiązkowy interfejs

#[Cache(refreshAfter: 3600)] # refreshAfter spowoduje, że po godzinie, przy następnym wywołaniu cache zostanie odświeżony asynchronicznie i zostanie zwrócony stary cache.
class GetCompanyConfig implements Cacheable, HashableInstance
{
    public function __construct(
        public readonly ?User $user,
        public readonly string $companyId,
    ) {}

    public function getHashableInstance(): Cacheable
    {
        return new self(null, $this->companyId); # obsłużony nadal zostanie oryginalny Message, ale ten posłuży do stworzenia unikalnego hasha, więc gdybyśmy nie usunęli kontekstu użytkownika, to cache byłby dostępny tylko dla niego, a nie dla całej firmy.
    }
}
```

A co jeśli nie mogę usunąć kontekstu użytkownika, choćby dlatego, że nie chce mi się pisać new self() z 20 argumentami konstruktora? **Mam dla Ciebie alternatywne rozwiązanie!**

```php
# src/Application/User/Query/GetUserConfig.php
use PBaszak\MessengerCacheBundle\Attribute\Cache; # obowiązkowy atrybut
use PBaszak\MessengerCacheBundle\Contract\Optional\UniqueHash; # opcjonalny interfejs
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable; # obowiązkowy interfejs

#[Cache(refreshAfter: 3600)]
class GetCompanyConfig implements Cacheable, UniqueHash
{
    public function __construct(
        public readonly ?User $user,
        public readonly string $companyId,
    ) {}

    public function getUniqueHash(): string
    {
        return 'company_' . $this->companyId;
    }
}
```
Czy tyle wystarczy? Czy cache nie będzie mieszał się z cachem innego Message, w którym będzie ten sam UniqueHash?<br>
**Nie będzie**. Klucz cache składa się jeszcze z hasha powstałego z pełnej nazwy klasy Message.

<br>

To już wszystko, jeśli chodzi o szybki start. Jesteś gotowy do wdrażania wydajnego cache w swojej aplikacji, z prostą implementacją, pozwalającą na tworzenie naprawdę zaawansowanych systemów cache'owania i inwalidacji cache, bo właśnie do takich celów ta paczka została stworzona.

<hr>
<hr>

## Konfiguracja ##

Utwórz lub skopiuj plik `messenger_cache.yaml`:
```sh
# kopiowanie
cp vendor/pbaszak/messenger-cache-bundle/config/packages/messenger_cache.yaml config/packages/messenger_cache.yaml
#
# lub
#
# tworzenie
touch config/packages/messenger_cache.yaml
```

```yaml
# config/packages/messenger_cache.yaml
messenger_cache:
    # If You use RefreshAsync (it's message) / refreshAfter (in Cache attribute argument) then
    # you must have declared time of live info that refresh was triggered. It's deleted
    # after succesful refresh. To short value may trigger more than one refresh action for 
    # specific item. Recommended is 10 minutes.
    refresh_triggered_ttl: 600
    # If You need handle cache events like hit, miss, refresh and stamps are not enough for
    # You then change this option to `true`. It add additional events to cache, but it costs performance
    use_events: true
    # aliases for pools to use them in cache attribute and cache invalidation attribute
    # aliases are required and `default` alias is required.
    pools:
        default: filesystem
        runtime: runtime
        redis: redis
    # this is default value and You don't need to add it, but if You want decorates different buses
    # you can declare them all here.
    decorated_message_buses:
        - cachedMessage.bus
    # message bus decorators whic You want add. The main cache decorator has 
    # priority 0 if higher it will be closer to main message bus.
    message_bus_decorators:
        '-1': PBaszak\MessengerCacheBundle\Tests\Helper\Domain\Decorator\LoggingMessageBusDecorator # this one decorator was only for tests. If You want logging decorator You have to make it, but before You start check `use_events`, maybe it will be better option to logging or metrics or anything You want like adding cache result header in response. Just make Your own EventListener ;).
```
Opis konfiguracji:
| Parametr | Opis | Wartości |
|----------|------|----------|
| `refresh_triggered_ttl` | Czas przechowywania informacji o tym, że asynchroniczna akcja odświeżenia zawartości cache została uruchomiona. W tym czasie nie zostanie dodana do kolejki kolejna akcja odświeżenia tego konkretnego cache. | `600` sekund, które są wartością rekomendowaną dla większości przypadków. |
| `use_events` | `true` / `false`, gdzie `false` jest wartością rekomendowaną. `true` spowoduje dodanie message bus dekoratora, który na podstawie zwróconych przez `MessengerCacheManager` oraz `MessageBusCacheDecorator` stampów (`StampInterface` z paczki `Symfony/Messenger`) | `false` - domyślnie, <br>`true` - jeśli tworzysz EventListenera dla cache. |
| `pools`  | Lista poolów obsługiwanych przez `MessengerCacheBundle`, obowiązkowy jest tylko `default`, który to będzie wybrany, jeśli w atrybucie `Cache` nie wskażesz innego poola. Używanie aliasów jest obowiązkowe. Adaptery muszą być serwisami, tj. możliwe, że będziesz musiał je zdefiniować w pliku `cache.yaml`, przykład poniżej tabelki. | `default: redis`<br>`runtime: runtime`<br>itd. w konwencji `$alias: $pool`. |
| `decorated_message_buses` | Cache nie jest z automatu przypisany do wszystkich message busy w Twoim projekcie. W tym miejscu możesz ustalić, które, sposród listy zawartej w pliku `config/packages/messenger.yaml` mają wspierać cache. Domyślna wartość to `cachedMessage.bus` i sprawia ona, że wystarczy, że nazwiesz argument swojego konstruktora `MessageBusInterface $cachedMessageBus`, aby została zastosowana. | `- cachedMessage.bus`<br>`- messenger.bus.default`<br>itd. w konwencji: `$bus` jako element tablicy. |
| `message_bus_decorators` | Lista dekoratorów przypisanych do wszystkich message busy wypisanych w punkcie `decorated_message_buses`. Domyślnie jest tutaj tylko `MessageBusCacheDecorator` z priorytetem równym `0`. Jednak, jeśli ustawisz opcje `use_events` na `true` to `MessageBusCacheDecorator` otrzyma priorytet `1`, a przed nim, z priorytetem `0` zostanie ustawiony `MessageBusCacheEventsDecorator`. Jak już pewnie rozumiesz, im niższa wartość priorytetu tym szybciej wybrany dekorator będzie obsługiwał `message`, a ten o najwyższym priorytecie będzie komunikował się bezpośrednio z `MessageBusInterface`. Zwróć jednak uwagę, że priorytety wyższe niż ten przypisany do `MessageBusCacheDecorator` nie powinny obsługiwać komunikacji z `MessengerCacheManager`, a jedynie obsługiwać dalszy dowolny proces, który chcesz zastosować przed ostatecznym `MessageBusInterface`. Standardowo inne dekoratory, z innych bibliotek najczęściej pojawią się przed wszystkimi innymi dekoratorami wynikającymi z tej listy. | `"-1": \App\MessageBus\Decorator\MyAwesomeDecorator`<br>jak widzisz konwencja dopuszcza wartości ujemne priorytetów i takie tutaj rekomendujemy. Zasada: `"$priorty": $decoratorClassString` |

Przykładowa deklaracja poolów jako serwisów:
```yaml
# config/cache.yaml
framework:
    cache:
        default_redis_provider: 'redis://redis:6379'
        pools:
            runtime: 
                adapter: cache.adapter.array
                tags: true
            filesystem: 
                adapter: cache.adapter.filesystem
            redis:
                adapter: cache.adapter.redis_tag_aware
                tags: true

```
**UWAGA: W twoim kodzie może (a raczej na pewno będzie) to wyglądać trochę inaczej.**

Istnieją jeszcze dwie komendy:
 - `PBaszak\MessengerCacheBundle\Message\InvalidateAsync`,
 - `PBaszak\MessengerCacheBundle\Message\RefreshAsync`,

musisz je dodać do obsługi asynchronicznej samodzielnie. Jeśli tego nie zrobisz, będą wykonywane synchronicznie, co wpłynie na wydajność Twojej aplikacji przy pobieraniu danych z cache.

<hr>
<hr>

## Użycie ##

### **Przykład nr 1 (Cache)** ###

Przykładowa klasa obsługiwana przez `Symfony Messenger` jako `Message`, posiada swój `Handler`, który za każdym razem zwraca losowy ciąg znaków.

```php
# src/Application/Query/GetRandomString.php
use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache()]
class GetRandomString implements Cacheable
{
}
```

Dowolny Manager, który wywołuje interesujący nas `Message` i zwraca jego odpowiedź:

```php
# src/Domain/Manager/StringsManager.php
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $cachedMessageBus)
    {
        $this->messageBus = $cachedMessageBus;
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

### **Przykład nr 2 (CacheInvalidation)** ###

```php
# src/Application/Query/GetRandomString.php
use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache(tags: ['string_tag'])]
class GetRandomString implements Cacheable
{
}
```
```php
# src/Application/Command/DoSth.php
use PBaszak\MessengerCacheBundle\Attribute\CacheInvalidation;
use PBaszak\MessengerCacheBundle\Contract\Required\CacheInvalidation;

#[Invalidate(['string_tag'])]
class DoSth implements CacheInvalidation
{
}
```
```php
# src/Domain/Manager/StringsManager.php
use App\Application\Command\DoSth;
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $cachedMessageBus)
    {
        $this->messageBus = $cachedMessageBus;
    }

    public function getAlwaysSameRandomString(): string
    {
        return $this->handle(
            new GetRandomString()
        );
    }

    public function doSth(): void
    {
        $this->handle(
            new DoSth()
        );
    }
}

$stringsManager = new StringsManager();
$result0 = $stringsManager->getAlwaysSameRandomString();
$stringsManager->doSth();
$result1 = $stringsManager->getAlwaysSameRandomString();

var_dump($result0 === $result1); // false
```

### **Przykład nr 3 (ForceCacheRefreshStamp)** ###

Dodawanie dekoratora:
```yaml
# config/packages/messenger_cache.yaml
message_bus_decorators:
    "-1": App\Infrastructure\Symfony\Messenger\MessageBusDecorator
```

Implementacja przykładowej dekoratora:
```php
# src/Infrastructure/Symfony/Messenger/MessageBusDecorator.php
use PBaszak\MessengerCacheBundle\Stamps\ForceCacheRefreshStamp;

class MessageBusDecorator implements MessageBusInterface
{
    private Request $request;
    private static array $cacheRefreshedMessages;

    public function __construct(
        private MessageBusInterface $decorated,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getMainRequest();
        $this->forceCacheRefresh = $requestStack->getMainRequest()->query->has('forceCacheRefresh');
    }

    /** @param StampInterface[] $stamps */
    public function dispatch(object $message, array $stamps = []): Envelope
    {
        if (!in_array($message, self::$cacheRefreshedMessages) && $this->request->query->has('forceCacheRefresh')) {
            $stamps = array_merge($stamps, [new ForceCacheRefreshStamp()]);
            self::$cacheRefreshedMessages = $message;
        }

        return $this->decorated->dispatch($message, $stamps);
    }
}
```

Tak przekazany parametr żądania spowoduje synchroniczne odświeżanie wszystkich cache w ramach żądania.
```sh
curl --location --request GET 'http://localhost/strings/random?forceCacheRefresh'
```
Dla bardziej precyzyjnych rozwiązań rekomenduję stworzenie własnej metodyki decydowania o tym, który cache należy odświeżyć i nierekomenduję odświeżania wszystkich możliwych cache w ramach jednego żądania. Osobiście stosuję funkcje warunkową `if ((new ReflectionClass($message))->getShortName() === $this->request->query->get('forceCacheRefresh')) { // add ForceCacheRefreshStamp }`, wtedy przykładowy parametr może wyglądać następująco: `?forceCacheRefresh=GetUserConfig`.

```php
# src/Domain/Manager/StringsManager.php
use App\Application\Query\GetRandomString;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\HandleTrait;

class StringsManager
{
    use HandleTrait;

    public function __construct(MessageBusInterface $cachedMessageBus)
    {
        $this->messageBus = $cachedMessageBus;
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

var_dump($result0 === $result1); // false
```

<hr>
<hr>

## Ustawienia szczegółowe ##

### **Atrybuty** ###

### Cache ###

| Parametr | Opis |
|:---------|:-----|
| `$ttl` | Czas życia cache w sekundach. Możesz też się zainteresować interfejsem `DynamicTtl`, który pozwoli Ci dynamicznie wybrać `ttl` dla cache. |
| `$refreshAfter` | Czas ważności cache w sekundach, po upłynięciu tego czasu, po kolejnym wywołaniu `Bundle` spróbuje odświeżyć ten cache. **UWAGA: Konieczne jest dodanie `PBaszak\MessengerCacheBundle\Message\RefreshAsync` do Twojego systemu kolejek opartego o `MessageBusInterface` (AMQP, Redis, Doctrine - zobacz paczki: `Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`)**. |
| `$pool` | Alias poolu, który zostanie użyty do obsługi cache. |
| `$tags` | Lista stałych tagów dla danego zasobu, ale może będziesz zainteresowany interfejsem `DynamicTags`, który pozwoli Ci na pełną customizację. |


### Invalidate ###

| Parametr | Opis |
|:---------|:-----|
| `$tags` | Lista stałych tagów, które mają zostać poddane inwalidacji. Możesz je zastąpić przez interfejs `DynamicTags` |
| `$pool` | Adapter, który ma zostać poddany inwalidacji, jeśli `null`, to wszystkie pooly `TagAwareAdapterInterface` będą poddane inwalidacji. |
| `$invalidateBeforeDispatch` | Jeśli potrzebujesz, inwalidacja cache może odbyć się przed wykonaniem właściwego `Message`. |
| `$invalidateOnException` | Jeśli obsługa `Message` zakończy się wystąpieniem wyjątku, to inwalidacja po wykonaniu `Message` nadal może zostać wykonana, jeśli tylko chcesz. |
| `$invalidateAsync` | Jeśli `true` oraz `PBaszak\MessengerCacheBundle\Message\InvalidateAsync` znajduje się w Twoim systemie kolejek (`Symfony/amqp-messenger`, `Symfony/redis-messenger`, `Symfony/doctrine-messenger`) jako wykonywane asynchronicznie, to inwalidacja cache odbędzie się asynchronicznie. |

### **Opcjonalne interfejsy** ###

| Interfejs | Opis |
|:----------|:-----|
| `CacheableCallback` | Metoda `isCacheable(): bool` pozwoli Ci dynamicznie zdecydować o użyciu bądź nie cache. |
| `DynamicTags` | Metoda `getDynamicTags(): array` pozwoli Ci dynamicznie dostarczyć tagi do obsługi cache (zastępuje właściwość `Cache(tags: [])`). |
| `DynamicTtl` | Metoda `getDynamicTtl(): int` pozwoli Ci dynamicznie dostarczyć wartość `ttl` w sekundach dla cache. |
| `HashableInstance` | Metoda `getHashableInstance(): Cacheable` powinna zwrócić obiekt `Message` w formie, którą można poddać hashowaniu. Możesz musieć użyć tego interfejsu, jeśli cache, który chcesz przechować powinien być dostępny dla wielu użytkowników i jednocześnie w `Message` posiadasz kontekst użytkownika. Dzięki tej metodzi pozbędziesz się go. |
| `UniqueHash` | Metoda `getUniqueHash(): string` pozwala Ci samodzielnie zdefiniować hash dla instancji `Message`. Możesz tego użyć zamiast `HashableInstance`. |
