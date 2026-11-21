<?php

declare(strict_types=1);

namespace Medas\ServiceManager\Mapping;

use Medas\Core\{
    Attributes\Service,
    CachedImplementorList,
    Interfaces\ImplementorFinder as ImplementorFinderInterface
};

/**
 * Lists every service that is, extends, or implements a given type. This is how a package finds all the
 * implementations other packages provide, like handlers or plugins.
 *
 * That includes every non-abstract class with `#[Service]` in one of the registered packages, and every class
 * bound to the type with `ServiceConfigBuilder::addTypeBinding()`. A service that extends another service isn't
 * listed for that parent class, only for its interfaces and its other parent classes.
 *
 * @assumes The `ServiceManager` is initialized; the list comes from its configuration through `sm()`.
 * @cache The service mapping is part of the cached `ServiceConfig`, so a newly added service is only found after
 *        the caches are cleared.
 * @notFor Resolving one service; use `ServiceManager::resolve()`, which throws when a type has more than one
 *         implementation.
 * @see CachedImplementorList
 */
#[Service]
class ImplementorFinder implements ImplementorFinderInterface
{
    /**
     * @return class-string[] Each class once, in no guaranteed order, so callers that care about order should sort
     *                        the result themselves.
     */
    public function find(string $interface): array
    {
        return sm()->config->mapping->getImplementors($interface);
    }
}
