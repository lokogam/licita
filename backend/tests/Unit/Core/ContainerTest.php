<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use App\Core\Exceptions\ContainerException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function test_it_autowires_concrete_classes_with_dependencies(): void
    {
        $container = new Container();

        $service = $container->make(FakeServiceWithDependency::class);

        $this->assertInstanceOf(FakeServiceWithDependency::class, $service);
        $this->assertInstanceOf(FakeDependency::class, $service->dependency);
    }

    public function test_it_resolves_interfaces_bound_to_implementations(): void
    {
        $container = new Container();
        $container->bind(FakeContract::class, FakeDependency::class);

        $this->assertInstanceOf(FakeDependency::class, $container->make(FakeContract::class));
    }

    public function test_it_returns_the_same_instance_for_singletons(): void
    {
        $container = new Container();
        $container->singleton(FakeDependency::class, fn (): FakeDependency => new FakeDependency());

        $this->assertSame($container->make(FakeDependency::class), $container->make(FakeDependency::class));
    }

    public function test_it_creates_new_instances_for_regular_bindings(): void
    {
        $container = new Container();

        $this->assertNotSame($container->make(FakeDependency::class), $container->make(FakeDependency::class));
    }

    public function test_it_uses_default_values_for_unresolvable_scalar_parameters(): void
    {
        $container = new Container();

        $this->assertSame(10, $container->make(FakeServiceWithDefault::class)->limit);
    }

    public function test_it_fails_when_a_dependency_cannot_be_resolved(): void
    {
        $this->expectException(ContainerException::class);

        (new Container())->make(FakeServiceWithScalar::class);
    }
}

interface FakeContract
{
}

final class FakeDependency implements FakeContract
{
}

final class FakeServiceWithDependency
{
    public function __construct(public readonly FakeDependency $dependency)
    {
    }
}

final class FakeServiceWithDefault
{
    public function __construct(public readonly int $limit = 10)
    {
    }
}

final class FakeServiceWithScalar
{
    public function __construct(public readonly string $name)
    {
    }
}
