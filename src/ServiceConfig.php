<?php

declare(strict_types=1);

namespace Medas\ServiceManager;

/**
 * Plain, serializable configuration state produced by ServiceConfigBuilder.
 * Contains no bootstrap machinery — only the data ServiceManager needs at runtime.
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
