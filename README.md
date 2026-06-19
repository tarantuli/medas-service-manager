# medas-service-manager

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Description

The DI container and application bootstrap for the Medas framework. `ServiceManager` ties together service discovery, object instantiation, package lifecycle, caching, and error handling into a single entry point.

**Bootstrap sequence:**

1. The user-supplied initializer closure is called to produce a `ServiceConfig`
2. The `ServiceConfig` is loaded from persistent cache if available, or built fresh and persisted on shutdown
3. `ObjectInstantiator` is initialized from the configured class
4. All registered packages have `initialize()` called, then `ready()` called (in registration order)
5. A shutdown function persists the config to cache if it was freshly built or mutated

**Service discovery:**

`ServiceFinder` scans each package's `sourceDirectory()` for PHP files, loads them via PSR-4, and records every class annotated with `#[Service]` in a `ServiceMapping`. The mapping is stored in cache so file scanning only happens on the first boot or after a cache clear. The mapping is keyed by interface/class name → implementation class name.

**Resolution:**

`resolve(string $type)` looks up the implementation for a type, instantiates it on first call (injecting all constructor dependencies via `ObjectInstantiator`), and caches the instance for the lifetime of the request. Calling `resolve()` with the same type always returns the same instance.

**`ImplementorFinder`** walks all registered service class names and returns every non-abstract class that implements a given interface. This is how plugins (event listeners, response handlers, parameter resolvers, etc.) are discovered automatically without manual registration.

**Cache priming (`CachePrimer`):**

On `postInstall()` (run after `composer update`), `CachePrimer::prime()` calls `primeCache()` on every service implementing `PrimesCache` and registers filesystem cache directories under `var/dirs-to-clear/` so the framework-level cache clear command knows which directories to wipe.

## Usage

### Package developer context

**Bootstrapping the application:**

```php
use Medas\ServiceManager\{ServiceConfig, ServiceManager};
use Medas\ObjectInstantiator\ObjectInstantiator;
use Medas\Cache\FileSystemCache;

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig(
        objectInstantiatorClass: ObjectInstantiator::class,
    );

    $config->addPackages([
        // Register all your packages here
        ServiceManagerPackage::instance(),
        LoggingPackage::instance(),
        EntityManagerPackage::instance(),
        // ...
    ]);

    return $config;
});
```

**Adding a persistent cache to the service manager:**

```php
use Medas\Cache\FileSystemCache;

$cache = new FileSystemCache(baseDirectory: 'var/cache/service-manager');

new ServiceManager(
    initializer: function (): ServiceConfig { /* ... */ },
    cache: $cache,
);
```

With a persistent cache, the `ServiceMapping` is read from disk on later requests and file scanning is skipped.

**Development mode — aggressive error handling:**

```php
$config = new ServiceConfig(
    objectInstantiatorClass: ObjectInstantiator::class,
    isDev: true,
);
```

In dev mode `AggressiveErrorPolicy` is used (converts warnings and notices to exceptions) instead of `BasicErrorPolicy`.

**Registering packages:**

```php
$config->addPackages([
    HttpRequestHandlerPackage::instance(),
    RoutingPackage::instance(),
]);

// Dev-only packages are silently skipped in production
$config->addDevPackages([
    DebugPackage::instance(),
]);
```

**Defining a package:**

```php
use Medas\Core\{AsSingleton, BasePackage, Interfaces\ServiceConfig};

class MyPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        // Packages listed here are automatically registered before this one
        return [
            EntityManagerPackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        // All #[Service] classes under this directory are discovered automatically
        return __DIR__;
    }

    public function initialize(ServiceConfig $config): void
    {
        // Called during bootstrap — register parameter resolvers, argument processors, etc.
        $config->addParameterResolver(service(MyResolver::class));

        parent::initialize($config);
    }

    public function ready(): void
    {
        // Called after all packages have been initialized
        // Use for cross-package wiring that requires other packages to be ready
        service(SomeRegistry::class)->register(service(MyHandler::class));
    }

    public function postInstall(): void
    {
        // Called by composer post-update scripts
        medas()->serviceManager()->cachePrimer->prime();
    }
}
```

**Manual type binding — resolving a specific implementation for a type:**

```php
// In ServiceConfig, before the container is built
$config->addTypeBinding(ApcuCache::class, CacheInterface::class);

// At runtime, after the container is built
$serviceManager->bindImplementation(service(ApcuCache::class), CacheInterface::class);
```

**Manual parameter binding — inject a specific value for a parameter:**

```php
$config->addManualBinding(
    class: DatabaseReporter::class,
    parameter: 'maxRetries',
    value: 5,
);
```

**Resolving a service:**

```php
// Via the global helper (most common)
$service = service(InvoiceService::class);

// Via the service manager directly
$service = medas()->serviceManager()->resolve(InvoiceService::class);
```

**Listing all registered services:**

```bash
php bin/medas service-manager:list-services

# Filter by a substring
php bin/medas service-manager:list-services Invoice
```

**Clearing all caches:**

```bash
php bin/medas service-manager:clear-caches
```

Clears the service manager's own config cache and all filesystem cache directories registered under `var/dirs-to-clear/`.

### Backend user context

**Config caching** — the `ServiceConfig` (including the full service mapping) is serialized and cached. This means after the first request, the file system is not scanned again. The cache must be manually invalidated after making changes (adding or removing `#[Service]` classes, registering new packages, etc.):

```bash
php bin/medas service-manager:clear-caches
# or
composer update  # triggers postInstall() → CachePrimer::prime()
```

**Shutdown persistence** — if the config was freshly built (cache miss) or the service mapping was mutated via `bindImplementation()`, the config is written to cache in a PHP shutdown function. This is more reliable than `__destruct()` because shutdown functions run at a predictable point before the GC tears down the object graph.

**`var/dirs-to-clear/`** — filesystem caches register themselves here so `clear-caches` knows which directories to wipe. Each entry is a SHA1-named file containing the cache base directory path.
