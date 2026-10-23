<?php

declare(strict_types=1);

namespace App\Core\Routing;

use Closure;

/**
 * A single registered route compiled to a regular expression.
 */
final class Route
{
    public readonly string $regex;

    public readonly bool $isStatic;

    /**
     * @param Closure|array{0: class-string, 1: string} $handler
     * @param list<string> $middleware Aliases with optional parameters, e.g. "auth", "can:tenders.create".
     */
    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly Closure|array $handler,
        public readonly array $middleware = [],
    ) {
        $this->isStatic = !str_contains($uri, '{');
        $this->regex = $this->compile($uri);
    }

    /**
     * Returns the captured parameters when the path matches, null otherwise.
     *
     * @return array<string, string>|null
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->regex, $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, fn (int|string $key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Turns "/tenders/{id:\d+}" into "#^/tenders/(?P<id>\d+)$#".
     */
    private function compile(string $uri): string
    {
        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}#',
            fn (array $m): string => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $uri,
        );

        return '#^' . $pattern . '$#';
    }
}
