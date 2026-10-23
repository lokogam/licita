<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ContainerException;
use Closure;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Minimal dependency injection container with reflection-based autowiring.
 */
final class Container
{
    /** @var array<string, Closure(Container): mixed> */
    private array $bindings = [];

    /** @var array<string, bool> */
    private array $shared = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /**
     * Registers how to build an abstract type: a factory closure or a concrete class name.
     *
     * @param Closure(Container): mixed|class-string|null $concrete
     */
    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        $concrete ??= $abstract;

        $this->bindings[$abstract] = $concrete instanceof Closure
            ? $concrete
            : fn (Container $container): mixed => $container->build($concrete);
        $this->shared[$abstract] = $shared;
        unset($this->instances[$abstract]);
    }

    /**
     * Registers a binding that is built once and reused afterwards.
     *
     * @param Closure(Container): mixed|class-string|null $concrete
     */
    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, shared: true);
    }

    /**
     * Stores an already built object as a shared instance.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolves a type from the container, autowiring constructor dependencies.
     *
     * @template T of object
     * @param class-string<T>|string $abstract
     * @return ($abstract is class-string<T> ? T : mixed)
     */
    public function make(string $abstract): mixed
    {
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        $object = isset($this->bindings[$abstract])
            ? ($this->bindings[$abstract])($this)
            : $this->build($abstract);

        if ($this->shared[$abstract] ?? false) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiates a concrete class resolving its constructor parameters.
     */
    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new ContainerException("Unable to resolve [{$class}]: class does not exist or is not bound.");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException("Unable to resolve [{$class}]: it is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter): mixed => $this->resolveParameter($class, $parameter),
            $constructor->getParameters(),
        );

        return $reflection->newInstanceArgs($arguments);
    }

    private function resolveParameter(string $class, ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        throw new ContainerException(
            "Unable to resolve parameter \${$parameter->getName()} of [{$class}].",
        );
    }
}
