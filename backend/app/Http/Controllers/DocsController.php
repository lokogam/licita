<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Http\Response;
use OpenApi\Generator;

/**
 * Serves the OpenAPI document generated from the code attributes and the Swagger UI.
 */
final class DocsController
{
    private const SWAGGER_UI_VERSION = '5.17.14';

    /**
     * GET /api/openapi.json
     */
    public function openapi(): Response
    {
        $spec = (new Generator())->generate([dirname(__DIR__, 2)]);

        return new Response(
            (string) $spec?->toJson(),
            200,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
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
