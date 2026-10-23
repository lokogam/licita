<?php

declare(strict_types=1);

namespace App\Core\Routing;

use App\Core\Container;
use App\Core\Exceptions\MethodNotAllowedHttpException;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use Closure;
use LogicException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Maps HTTP method + path to controller actions or closures.
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    private string $prefix = '';

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     */
    public function get(string $uri, Closure|array $handler): void
    {
        $this->add('GET', $uri, $handler);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     */
    public function post(string $uri, Closure|array $handler): void
    {
        $this->add('POST', $uri, $handler);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     */
    public function put(string $uri, Closure|array $handler): void
    {
        $this->add('PUT', $uri, $handler);
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     */
    public function delete(string $uri, Closure|array $handler): void
    {
        $this->add('DELETE', $uri, $handler);
    }

    /**
     * Registers the routes defined in the callback under a common prefix.
     *
     * @param callable(Router): void $routes
     */
    public function group(string $prefix, callable $routes): void
    {
        $previous = $this->prefix;
        $this->prefix = $previous . '/' . trim($prefix, '/');

        try {
            $routes($this);
        } finally {
            $this->prefix = $previous;
        }
    }

    /**
     * Registered routes (used to verify the API documentation covers them all).
     *
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Finds the route for the request and executes its handler.
     */
    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->orderedRoutes() as $route) {
            $params = $route->match($request->path());
            if ($params === null) {
                continue;
            }

            if ($route->method !== $request->method()) {
                $allowedMethods[] = $route->method;
                continue;
            }

            return $this->run($route, $request->withRouteParams($params));
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedHttpException(array_values(array_unique($allowedMethods)));
        }

        throw new NotFoundHttpException();
    }

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     */
    private function add(string $method, string $uri, Closure|array $handler): void
    {
        $path = '/' . trim($this->prefix . '/' . trim($uri, '/'), '/');
        $this->routes[] = new Route($method, $path, $handler);
    }

    /**
     * Static routes are checked first so "/tenders/export" never matches "/tenders/{id}".
     *
     * @return list<Route>
     */
    private function orderedRoutes(): array
    {
        $static = array_filter($this->routes, fn (Route $route): bool => $route->isStatic);
        $dynamic = array_filter($this->routes, fn (Route $route): bool => !$route->isStatic);

        return [...array_values($static), ...array_values($dynamic)];
    }

    private function run(Route $route, Request $request): Response
    {
        if ($route->handler instanceof Closure) {
            $reflection = new ReflectionFunction($route->handler);
            $result = ($route->handler)(...$this->resolveArguments($reflection, $request));
        } else {
            [$class, $method] = $route->handler;
            $controller = $this->container->make($class);
            $reflection = new ReflectionMethod($controller, $method);
            $result = $reflection->invokeArgs($controller, $this->resolveArguments($reflection, $request));
        }

        if (!$result instanceof Response) {
            throw new LogicException("Route [{$route->method} {$route->uri}] must return a Response.");
        }

        return $result;
    }

    /**
     * Injects the current Request and any container-resolvable dependency.
     *
     * @return list<mixed>
     */
    private function resolveArguments(ReflectionFunctionAbstract $function, Request $request): array
    {
        $arguments = [];

        foreach ($function->getParameters() as $parameter) {
            $type = $parameter->getType();
            $className = $type instanceof ReflectionNamedType && !$type->isBuiltin() ? $type->getName() : null;

            $arguments[] = match (true) {
                $className === Request::class => $request,
                $className !== null => $this->container->make($className),
                $request->route($parameter->getName()) !== null => $request->route($parameter->getName()),
                $parameter->isDefaultValueAvailable() => $parameter->getDefaultValue(),
                default => throw new LogicException("Cannot resolve route argument \${$parameter->getName()}."),
            };
        }

        return $arguments;
    }
}
