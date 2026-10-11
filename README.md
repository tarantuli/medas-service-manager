# medas-service-manager

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Description

The DI container and application bootstrap for the Medas framework. `ServiceManager` ties together service discovery, object instantiation, package lifecycle, caching and error handling into a single entry point.

**Bootstrap sequence:**

1. The cache manager is set up, with the cache passed to the constructor as the default cache
2. The `ServiceConfig` is loaded from that cache. Only when it isn't cached, the user-supplied initializer closure is called to produce a `ServiceConfigBuilder`, all registered packages have `initialize()` called and the built config is stored in the cache
3. The configured object instantiator is created with the registered parameter resolvers, argument processors and the `isDev` flag
4. All registered packages have `ready()` called (in registration order) — on every request, also when the config came from the cache

**Service discovery:**

`ServiceFinder` scans each package's `sourceDirectory()` for PHP files, loads them via PSR-4 and records every class annotated with `#[Service]` in a `ServiceMapping`. The mapping is stored in cache so file scanning only happens on the first boot or after a cache clear. The mapping is keyed by interface/class name → implementation class name.

**Resolution:**

`resolve(string $type)` looks up the implementation for a type, instantiates it on first call (injecting all constructor dependencies via `ObjectInstantiator`) and caches the instance for the lifetime of the request. Calling `resolve()` with the same type always returns the same instance.

`resolveLazy(string $type)` (from the `ResolvesLazily` interface) is what dependency injection uses: a service that hasn't been instantiated yet is registered as a lazy object, whose constructor only runs when the service is first used. Later calls to `resolve()` return that same object. A service that is injected but never used is never constructed, and neither are its own dependencies. Mark a service `#[Service(lazy: false)]` when its constructor must run when it's injected. See the object instantiator's documentation for details.

**`ImplementorFinder`** walks all registered service class names and returns every non-abstract class that implements a given interface. This is how plugins (event listeners, response handlers, parameter resolvers, etc.) are discovered automatically without manual registration.

**Cache priming (`CachePrimer`):**

On `postInstall()` (run after `composer update`), `CachePrimer::prime()` calls `primeCache()` on every service implementing `PrimesCache` and registers filesystem cache directories under `var/dirs-to-clear/` so the framework-level cache clear command knows which directories to wipe.

## Usage

### Package developer context

**Bootstrapping the application:**

```php
use Medas\ServiceManager\{ServiceConfigBuilder, ServiceManager};
use Medas\ObjectInstantiator\ObjectInstantiator;

new ServiceManager(function (): ServiceConfigBuilder {
    $builder = new ServiceConfigBuilder(ObjectInstantiator::class);

    $builder->addPackages([
        // Register your root packages here; their dependencies are added automatically
        AppPackage::instance(),
    ]);

    return $builder;
});
```

**Adding a persistent cache to the service manager:**

```php
use Medas\Cache\{ApcuCache, FileSystemCache};

// APCu for web requests; the CLI usually has APCu disabled (apc.enable_cli), so fall back to the file system there
$cache = ApcuCache::isAvailable()
    ? new ApcuCache('my-app')
    : new FileSystemCache(__DIR__ . '/var/cache');

new ServiceManager(
    initializer: function (): ServiceConfigBuilder { /* ... */ },
    cache: $cache,
);
```

With a persistent cache, the `ServiceConfig` (including the service mapping) is read from the cache on later requests and file scanning is skipped. The cache passed here becomes the default cache, which the framework also uses for other compiled data, such as the object instantiator's resolution plans and the entity metadata.

**Development mode — aggressive error handling:**

```php
$builder = new ServiceConfigBuilder(ObjectInstantiator::class, isDev: true);
```

In dev mode `AggressiveErrorPolicy` is used (converts warnings and notices to exceptions) instead of `BasicErrorPolicy`, dev packages are registered, and the object instantiator checks its cached resolution plans against the source files, so constructor changes take effect without clearing the cache.

**Registering packages:**

```php
$builder->addPackages([
    HttpRequestHandlerPackage::instance(),
    RoutingPackage::instance(),
]);

// Dev-only packages are silently skipped in production
$builder->addDevPackages([
    DebugPackage::instance(),
]);
```

**Defining a package:**

```php
use Medas\Core\{AsSingleton, BasePackage, Interfaces\ServiceConfigBuilder};

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

    public function initialize(ServiceConfigBuilder $config): void
    {
        // Called only when the config is built (not when it comes from the cache) — register parameter resolvers,
        // argument processors, etc. They are registered by class name and instantiated without arguments.
        $config->addParameterResolver(MyResolver::class);

        parent::initialize($config);
    }

    public function ready(): void
    {
        // Called on every request, after the config has been loaded or built
        // Use for setup that requires live services, or that can't be cached
        service(SomeRegistry::class)->register(service(MyHandler::class));
    }

    public function postInstall(): void
    {
        // Called by the post-install script after composer install/update
        service(MigrationRunner::class)->runPending();
    }
}
```

**Manual type binding — resolving a specific implementation for a type:**

```php
// In the ServiceConfigBuilder, e.g. in a package's initialize()
$config->addTypeBinding(ApcuCache::class, CacheInterface::class);
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
php bin/medas services:list

# Filter by a substring
php bin/medas services:list Invoice
```

**Clearing all caches:**

```bash
php bin/medas services:clear-caches
```

Empties every directory registered under `var/dirs-to-clear/` (like the post-install script does), then calls `clear()` on every cache registered with the cache manager. Emptying the registered directories is what reaches caches outside this command-line process: it removes the file caches' entries, and the APCu cache's generation marker, so the web server's APCu cache starts a new generation (see `medas-cache`). Registered paths that are a file system root, the project root or one of its parents are skipped.

### Backend user context

**Config caching** — the `ServiceConfig` (including the full service mapping) is serialized and cached. This means after the first request, the file system is not scanned again. The cache must be cleared after making changes (adding or removing `#[Service]` classes, registering new packages, etc.):

```bash
php bin/medas services:clear-caches
# or
composer update  # runs the post-install scripts, which clear the caches
```

**`var/dirs-to-clear/`** — file system caches (and the APCu cache's generation directory) register their directories here, so the post-install script `clear-cache-dirs.php` knows which directories to empty. Each entry is a SHA1-named file containing the directory path.
