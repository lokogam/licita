<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Services\OpenApiSpec;

/**
 * Serves the OpenAPI document generated from the code attributes and the Swagger UI.
 */
final class DocsController
{
    private const SWAGGER_UI_VERSION = '5.17.14';

    public function __construct(private readonly OpenApiSpec $spec)
    {
    }

    /**
     * GET /api/openapi.json - cached document with ETag revalidation.
     */
    public function openapi(Request $request): Response
    {
        $json = $this->spec->json();
        $headers = [
            'ETag' => '"' . sha1($json) . '"',
            'Cache-Control' => 'public, max-age=3600',
        ];

        if ($request->header('If-None-Match') === $headers['ETag']) {
            return new Response('', 304, $headers);
        }

        return new Response($json, 200, ['Content-Type' => 'application/json; charset=utf-8'] + $headers);
    }

    /**
     * GET /api/docs
     */
    public function ui(): Response
    {
        $cdn = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@' . self::SWAGGER_UI_VERSION;

        $html = <<<HTML
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>API Licitaciones | Swagger</title>
                <link rel="stylesheet" href="{$cdn}/swagger-ui.css">
            </head>
            <body>
                <div id="swagger-ui"></div>
                <script src="{$cdn}/swagger-ui-bundle.js"></script>
                <script>
                    window.ui = SwaggerUIBundle({ url: '/api/openapi.json', dom_id: '#swagger-ui', deepLinking: true });
                </script>
            </body>
            </html>
            HTML;

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
