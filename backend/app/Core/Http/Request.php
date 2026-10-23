<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Immutable representation of an incoming HTTP request.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files  Normalized tree of UploadedFile instances.
     * @param array<string, string> $headers Header names in lower case.
     * @param array<string, string> $routeParams
     */
    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
        private readonly array $headers,
        private readonly array $routeParams = [],
        private readonly bool $malformedBody = false,
    ) {
    }

    /**
     * Builds a request from PHP superglobals.
     */
    public static function capture(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return self::create(
            (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $_GET,
            $_POST,
            self::normalizeFiles($_FILES),
            $headers,
            (string) file_get_contents('php://input'),
        );
    }

    /**
     * Builds a request programmatically (used by tests and capture()).
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     * @param array<string, string> $headers
     * @param string|null $rawBody Request payload; decoded when the content type is JSON.
     */
    public static function create(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $files = [],
        array $headers = [],
        ?string $rawBody = null,
    ): self {
        $headers = array_change_key_case($headers, CASE_LOWER);
        $malformed = false;

        if ($rawBody !== null && $rawBody !== '' && str_contains(strtolower($headers['content-type'] ?? ''), 'json')) {
            $decoded = json_decode($rawBody, true);
            $malformed = !is_array($decoded);
            $body = is_array($decoded) ? $decoded : [];
        }

        $parts = parse_url($uri);
        $path = '/' . trim(rawurldecode($parts['path'] ?? '/'), '/');

        if (isset($parts['query'])) {
            parse_str($parts['query'], $uriQuery);
            $query = array_merge($uriQuery, $query);
        }

        $method = strtoupper($method);
        if ($method === 'POST' && is_string($body['_method'] ?? null)) {
            $spoofed = strtoupper($body['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }
        unset($body['_method']);

        /** @var array<string, mixed> $query */
        return new self(
            $method,
            $path,
            $query,
            $body,
            $files,
            $headers,
            [],
            $malformed,
        );
    }

    /**
     * Returns a copy with the parameters captured by the matched route.
     *
     * @param array<string, string> $params
     */
    public function withRouteParams(array $params): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->body,
            $this->files,
            $this->headers,
            $params,
            $this->malformedBody,
        );
    }

    /**
     * True when a JSON payload could not be decoded.
     */
    public function hasMalformedBody(): bool
    {
        return $this->malformedBody;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * Reads a query string value.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Reads a value from the body, falling back to the query string.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Returns query, body and files merged (body wins over query; files are merged recursively).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_replace_recursive($this->query, $this->body, $this->files);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function route(string $name): ?string
    {
        return $this->routeParams[$name] ?? null;
    }

    /**
     * Returns the uploaded files under a key as a flat list.
     *
     * @return list<UploadedFile>
     */
    public function files(string $key): array
    {
        $value = $this->files[$key] ?? [];
        $items = is_array($value) ? $value : [$value];

        return array_values(array_filter($items, fn (mixed $item): bool => $item instanceof UploadedFile));
    }

    /**
     * Converts PHP's $_FILES structure into a tree of UploadedFile objects
     * mirroring the field names (e.g. documents[0][file]).
     *
     * @param array<string, mixed> $files
     * @return array<string, mixed>
     */
    public static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $field => $spec) {
            if (!is_array($spec) || !isset($spec['name'], $spec['tmp_name'], $spec['error'])) {
                continue;
            }
            $normalized[$field] = self::buildFileTree(
                $spec['name'],
                $spec['type'] ?? '',
                $spec['tmp_name'],
                $spec['error'],
                $spec['size'] ?? 0,
            );
        }

        return $normalized;
    }

    private static function buildFileTree(mixed $name, mixed $type, mixed $tmp, mixed $error, mixed $size): mixed
    {
        if (!is_array($name)) {
            return new UploadedFile((string) $tmp, (string) $name, (string) $type, (int) $size, (int) $error);
        }

        $tree = [];
        foreach (array_keys($name) as $key) {
            $tree[$key] = self::buildFileTree(
                $name[$key],
                is_array($type) ? ($type[$key] ?? '') : '',
                is_array($tmp) ? ($tmp[$key] ?? '') : '',
                is_array($error) ? ($error[$key] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE,
                is_array($size) ? ($size[$key] ?? 0) : 0,
            );
        }

        return $tree;
    }
}
