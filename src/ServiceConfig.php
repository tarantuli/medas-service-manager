<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

/**
 * The configuration `ServiceManager` needs at runtime, produced by `ServiceConfigBuilder`. It holds plain data only,
 * no bootstrap machinery, so it can be serialized and cached.
 *
 * @see ServiceConfigBuilder
 */
readonly class ServiceConfig
{
    public function __construct(
        public string                 $objectInstantiatorClass,
        public string                 $errorPolicy,
        public bool                   $isDev,
        public Mapping\ServiceMapping $mapping,
        public array                  $packageClasses,
        public array                  $manualBindings,
        public array                  $parameterResolvers,
        public array                  $argumentProcessors,
        public array                  $exceptionHandlers,
    )
    {
    }
}
