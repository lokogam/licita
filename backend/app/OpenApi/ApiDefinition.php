<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Global OpenAPI definition and shared schemas.
 * Endpoints are documented with attributes on each controller action.
 */
#[OA\OpenApi(openapi: '3.1.0', security: [['bearerAuth' => []]])]
#[OA\Info(
    version: '1.0.0',
    description: 'API REST del módulo de licitaciones: creación, edición con documentos, listado con filtros, '
        . 'detalle, exportación a Excel y catálogo de actividades UNSPSC.',
    title: 'API Módulo de Licitaciones',
)]
#[OA\Server(url: '/', description: 'Servidor actual')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Access token obtenido en POST /api/auth/login (expira en 15 minutos).',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\Tag(name: 'Autenticación', description: 'Inicio y cierre de sesión, renovación del token')]
#[OA\Tag(name: 'Usuarios', description: 'Gestión de usuarios y roles (solo administradores)')]
#[OA\Tag(name: 'Licitaciones', description: 'Gestión de licitaciones (ofertas)')]
#[OA\Tag(name: 'Documentos', description: 'Documentos adjuntos a una licitación')]
#[OA\Tag(name: 'Actividades', description: 'Clasificador de bienes y servicios UNSPSC')]
#[OA\Tag(name: 'Sistema', description: 'Estado del servicio')]
#[OA\Schema(
    schema: 'Status',
    required: ['value', 'label'],
    properties: [
        new OA\Property(property: 'value', type: 'string', enum: ['scheduled', 'open', 'closed']),
        new OA\Property(property: 'label', type: 'string', enum: ['Programada', 'Abierta', 'Cerrada']),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Activity',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 120),
        new OA\Property(property: 'product_code', type: 'integer', example: 43211503),
        new OA\Property(property: 'product', type: 'string', example: 'Computadores portátiles'),
        new OA\Property(property: 'class_name', type: 'string', example: 'Computadores'),
        new OA\Property(property: 'family', type: 'string', example: 'Equipo informático y accesorios'),
        new OA\Property(property: 'segment', type: 'string', example: 'Difusión de Tecnologías de Información'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Document',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', maxLength: 100, example: 'Pliego de condiciones'),
        new OA\Property(property: 'description', type: 'string', maxLength: 200, example: 'Documento principal'),
        new OA\Property(property: 'original_name', type: 'string', example: 'pliego.pdf'),
        new OA\Property(property: 'mime_type', type: 'string', enum: ['application/pdf', 'application/zip']),
        new OA\Property(property: 'size', type: 'integer', description: 'Bytes', example: 524288),
        new OA\Property(property: 'created_at', type: 'string', example: '2026-10-22 10:15:00'),
        new OA\Property(property: 'download_url', type: 'string', example: '/api/tenders/1/documents/1/download'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'TenderSummary',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'consecutive', type: 'string', example: 'O-0001-26'),
        new OA\Property(property: 'subject', type: 'string', example: 'Compra de portátiles'),
        new OA\Property(property: 'description', type: 'string', example: 'Adquisición de 50 equipos'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-11-01'),
        new OA\Property(property: 'closing_date', type: 'string', format: 'date', example: '2026-11-15'),
        new OA\Property(property: 'status', ref: '#/components/schemas/Status'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Tender',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'consecutive', type: 'string', example: 'O-0001-26'),
        new OA\Property(property: 'subject', type: 'string', maxLength: 150, example: 'Compra de portátiles'),
        new OA\Property(property: 'description', type: 'string', maxLength: 400, example: 'Adquisición de 50 equipos'),
        new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD', 'EUR']),
        new OA\Property(property: 'budget', type: 'string', example: '150000000.50'),
        new OA\Property(property: 'activity', ref: '#/components/schemas/Activity'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-11-01'),
        new OA\Property(property: 'start_time', type: 'string', example: '08:00'),
        new OA\Property(property: 'closing_date', type: 'string', format: 'date', example: '2026-11-15'),
        new OA\Property(property: 'closing_time', type: 'string', example: '17:30'),
        new OA\Property(property: 'status', ref: '#/components/schemas/Status'),
        new OA\Property(
            property: 'documents',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Document'),
        ),
        new OA\Property(property: 'created_at', type: 'string', example: '2026-10-22 10:15:00'),
        new OA\Property(property: 'updated_at', type: 'string', example: '2026-10-22 10:15:00'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'TenderInput',
    required: [
        'subject',
        'description',
        'currency',
        'budget',
        'activity_id',
        'start_date',
        'start_time',
        'closing_date',
        'closing_time',
    ],
    properties: [
        new OA\Property(property: 'subject', type: 'string', maxLength: 150, example: 'Compra de portátiles'),
        new OA\Property(property: 'description', type: 'string', maxLength: 400, example: 'Adquisición de 50 equipos'),
        new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD', 'EUR'], example: 'COP'),
        new OA\Property(
            property: 'budget',
            description: 'Mayor a 0, máximo 2 decimales, punto como separador',
            type: 'string',
            pattern: '^\d+(\.\d{1,2})?$',
            example: '150000000.50',
        ),
        new OA\Property(property: 'activity_id', type: 'integer', example: 120),
        new OA\Property(property: 'start_date', type: 'string', format: 'date', example: '2026-11-01'),
        new OA\Property(property: 'start_time', type: 'string', pattern: '^\d{2}:\d{2}$', example: '08:00'),
        new OA\Property(
            property: 'closing_date',
            description: 'No puede ser menor a la fecha de inicio',
            type: 'string',
            format: 'date',
            example: '2026-11-15',
        ),
        new OA\Property(
            property: 'closing_time',
            description: 'Si la fecha de cierre es igual a la de inicio, debe ser mayor a la hora de inicio',
            type: 'string',
            pattern: '^\d{2}:\d{2}$',
            example: '17:30',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 42),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'to', type: 'integer', example: 10, nullable: true),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Error',
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Recurso no encontrado.')],
    type: 'object',
)]
#[OA\Schema(
    schema: 'ValidationError',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Los datos enviados no son válidos.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            example: ['subject' => ['El campo objeto es obligatorio.']],
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email', 'role', 'abilities', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Administrador Demo'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@suplos.test'),
        new OA\Property(
            property: 'role',
            properties: [
                new OA\Property(property: 'value', type: 'string', enum: ['admin', 'editor', 'viewer']),
                new OA\Property(property: 'label', type: 'string', enum: ['Administrador', 'Editor', 'Lector']),
            ],
            type: 'object',
        ),
        new OA\Property(
            property: 'abilities',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['tenders.view', 'tenders.create'],
        ),
        new OA\Property(property: 'created_at', type: 'string', example: '2026-10-24 09:00:00'),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'UserInput',
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 150),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 10),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'editor', 'viewer']),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'Session',
    required: ['access_token', 'token_type', 'expires_in', 'user'],
    properties: [
        new OA\Property(property: 'access_token', type: 'string'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_in', type: 'integer', example: 900),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
    type: 'object',
)]
#[OA\Response(
    response: 'Unauthorized',
    description: 'Sin sesión o token inválido/expirado',
    content: new OA\JsonContent(ref: '#/components/schemas/Error'),
)]
#[OA\Response(
    response: 'Forbidden',
    description: 'El rol del usuario no tiene permiso',
    content: new OA\JsonContent(ref: '#/components/schemas/Error'),
)]
#[OA\Response(
    response: 'TooManyRequests',
    description: 'Inicio de sesión bloqueado temporalmente (ver cabecera Retry-After)',
    content: new OA\JsonContent(ref: '#/components/schemas/Error'),
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Recurso no encontrado',
    content: new OA\JsonContent(ref: '#/components/schemas/Error'),
)]
#[OA\Response(
    response: 'UnprocessableEntity',
    description: 'Errores de validación',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationError'),
)]
final class ApiDefinition
{
}
