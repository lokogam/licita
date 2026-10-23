<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Response whose body is a JSON document.
 */
final class JsonResponse extends Response
{
    /**
     * @param array<mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly array $data,
        int $status = 200,
        array $headers = [],
    ) {
        parent::__construct(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            $status,
            array_merge(['Content-Type' => 'application/json; charset=utf-8'], $headers),
        );
    }

    /**
     * Decoded payload, handy for assertions.
     *
     * @return array<mixed>
     */
    public function data(): array
    {
        return $this->data;
    }
}
