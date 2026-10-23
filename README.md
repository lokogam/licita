# Módulo de Licitaciones

Módulo para **crear, editar (con carga de documentos), listar, filtrar, consultar y exportar a Excel** licitaciones (ofertas).

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 **sin framework**, patrón MVC, Eloquent standalone (`illuminate/database`) como ORM |
| API | REST JSON documentada con OpenAPI 3.1 + Swagger UI |
| Frontend | Vue 3 (Composition API) + Vite, Axios, Bootstrap 5.3 + Bootstrap Icons |
| Base de datos | MySQL 8.4 (compatible con MariaDB 10.6+) |
| Infraestructura | Docker: un Dockerfile por servicio (frontend / backend) + Docker Compose |
| Calidad | TDD con PHPUnit, Vitest y Playwright · PHPStan nivel 8 · PSR-12 · GitHub Actions |

---

## Contenido

1. [Ejecución rápida con Docker](#ejecución-rápida-con-docker)
2. [Entorno de desarrollo](#entorno-de-desarrollo)
3. [Pruebas](#pruebas)
4. [Base de datos (carpeta `bd`)](#base-de-datos-carpeta-bd)
5. [Arquitectura](#arquitectura)
6. [API](#api)
7. [Reglas de negocio y validaciones](#reglas-de-negocio-y-validaciones)
8. [Seguridad](#seguridad)
9. [Flujo de trabajo con Git](#flujo-de-trabajo-con-git)

---

## Ejecución rápida con Docker

**Requisitos:** Docker 24+ con Docker Compose v2.

```bash
git clone https://github.com/lokogam/suplos-licitaciones.git
cd suplos-licitaciones
cp .env.example .env        # opcional: puertos y credenciales
docker compose up -d --build
```

| Servicio | URL |
|---|---|
| Aplicación | http://localhost:8080 |
| Documentación de la API (Swagger) | http://localhost:8080/api/docs |
| Especificación OpenAPI | http://localhost:8080/api/openapi.json |
| MySQL (desde el host) | `localhost:3307` · usuario `suplos` / `secret` |

La primera vez MySQL ejecuta automáticamente los scripts de `bd/`: crea las tablas y carga las **49.022 actividades** del clasificador UNSPSC. Tarda unos segundos; `docker compose up --wait` espera a que todo esté sano.

Para detener: `docker compose down` (agregue `-v` para borrar también datos y documentos).

### Servicios

```
                 ┌─────────────────────────┐
  navegador ───► │ frontend (Nginx :8080)  │  sirve la SPA de Vue
                 │  /api/* ──► proxy ──────┼──► backend (PHP 8.2 + Apache)  ──► db (MySQL 8.4)
                 └─────────────────────────┘        documentos en un volumen   tablas + UNSPSC
```

- `frontend/Dockerfile`: etapa `development` (Vite con recarga en caliente), `build` y `production` (Nginx).
- `backend/Dockerfile`: etapa `development` (código montado) y `production` (dependencias y código dentro de la imagen).

---

## Entorno de desarrollo

```bash
docker compose -f compose.yaml -f compose.dev.yaml up -d --build
```

| Servicio | URL |
|---|---|
| Vite (recarga en caliente) | http://localhost:5173 |
| API directa | http://localhost:8081/api |

El código de `backend/` y `frontend/` se monta en los contenedores, así que los cambios se ven al instante.

### Sin Docker (opcional)

```bash
# Backend (PHP 8.2 con pdo_mysql, bcmath, zip, gd, fileinfo)
cd backend && composer install && cp .env.example .env
php -S localhost:8080 -t public public/index.php

# Frontend
cd frontend && npm install && npm run dev   # proxy de /api hacia localhost:8080
```

---

## Pruebas

El proyecto se construyó con **TDD**: en el historial de Git cada funcionalidad tiene primero un commit `test(...)` (en rojo) y luego el `feat(...)` que lo pone en verde.

| Suite | Comando | Qué cubre |
|---|---|---|
| Backend unitarias + integración | `docker compose -f compose.yaml -f compose.dev.yaml exec backend composer test` | Router, contenedor, validador, consecutivo, servicios y endpoints contra MySQL real (cada prueba en una transacción que se revierte) |
| Análisis estático | `... exec backend composer analyse` | PHPStan nivel 8 |
| Estilo | `... exec backend composer lint` | PSR-12 (PHP_CodeSniffer) |
| Frontend | `cd frontend && npm test` | Validadores, formateadores, composables, componentes y vistas (Vitest + Vue Test Utils) |
| End-to-end | `cd frontend && npx playwright install chromium && npm run test:e2e` | Flujo completo en el navegador contra el stack de Docker (escritorio y móvil) |

Las pruebas de backend usan una base de datos aparte (`suplos_tenders_test`), creada automáticamente a partir de `bd/01_schema.sql`. Nunca tocan los datos de la aplicación.

**Totales actuales:** 141 pruebas de backend, 67 de frontend y 5 escenarios E2E. Todas corren en GitHub Actions (`.github/workflows/ci.yml`).

---

## Base de datos (carpeta `bd`)

| Archivo | Contenido |
|---|---|
| `bd/01_schema.sql` | Estructura: tablas, llaves foráneas, índices y restricciones `CHECK` |
| `bd/02_activities.sql` | Datos de las actividades (clasificador UNSPSC en español, 49.022 registros) |

Instalación manual (sin Docker):

```bash
mysql -u root -p -e "CREATE DATABASE suplos_tenders CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p suplos_tenders < bd/01_schema.sql
mysql -u root -p suplos_tenders < bd/02_activities.sql
```

`02_activities.sql` se generó desde el Excel oficial con `php backend/bin/unspsc-to-sql.php <archivo.xlsx>`, con pruebas propias del parser y del escape de SQL.

### Equivalencia con el modelo de la prueba

Todo el código, incluidas tablas y columnas, está en **inglés**; los textos que ve el usuario están en español.

| Especificación (`ofertas`) | Implementación (`tenders`) |
|---|---|
| id | id |
| consecutivo | consecutive (único) |
| objeto | subject `VARCHAR(150)` |
| descripcion | description `VARCHAR(400)` |
| moneda | currency `VARCHAR(3)` (`COP`, `USD`, `EUR`) |
| presupuesto | budget `DECIMAL(15,2)` (> 0) |
| actividad_id | activity_id → `activities.id` |
| fecha_inicio / hora_inicio | start_date / start_time |
| fecha_cierre / hora_cierre | closing_date / closing_time |
| estado | status `VARCHAR(20)` |
| creado_en / actualizado_en | created_at / updated_at |

| `actividades` | `activities` |
|---|---|
| codigo_segmento / segmento | segment_code / segment |
| codigo_familia / familia | family_code / family |
| codigo_clase / clase | class_code / class_name |
| codigo_producto / producto | product_code (único) / product |

| `ofertas_documentos` | `tender_documents` |
|---|---|
| licitacion_id | tender_id → `tenders.id` (cascada) |
| titulo / descripcion | title `VARCHAR(100)` / description `VARCHAR(200)` |
| archivo | file_path (ruta interna) + original_name, mime_type, size |
| creado_en | created_at |

Tabla adicional: `tender_sequences(year, last_number)`, que lleva el contador del consecutivo por año.

---

## Arquitectura

### Backend: MVC sin framework, con organización tipo Laravel

```
backend/
├── public/index.php            Front controller (único punto de entrada)
├── bootstrap/app.php           Arma la aplicación: .env, configuración, ORM, contenedor y rutas
├── config/                     app.php, database.php
├── routes/api.php              Definición de rutas
├── app/
│   ├── Core/                   Mini-framework propio
│   │   ├── Container.php       Inyección de dependencias con autowiring (Reflection)
│   │   ├── Kernel.php          Request → Response, CORS, JSON inválido → 400
│   │   ├── ExceptionHandler.php  Errores → JSON uniforme (sin detalles internos en producción)
│   │   ├── Http/               Request, Response, JsonResponse, FileResponse, UploadedFile
│   │   └── Routing/            Router con parámetros tipados ({id:\d+}) y grupos
│   ├── Http/
│   │   ├── Controllers/        Controladores delgados: validan → llaman al servicio → devuelven la vista
│   │   ├── Requests/           Reglas de validación por endpoint (Store/UpdateTenderRequest)
│   │   └── Resources/          ★ Vista: representación JSON de los modelos
│   ├── Views/Exports/          ★ Vista: plantilla del reporte Excel
│   ├── Models/                 ★ Modelo: Eloquent (Tender, Activity, TenderDocument)
│   ├── Services/               Lógica de negocio (TenderService, ConsecutiveGenerator, DocumentStorage)
│   ├── Repositories/           Consultas de lectura (filtros, paginación, búsqueda)
│   ├── Validation/             Validador propio + reglas (Required, Max, Decimal, Mimes…)
│   ├── Enums/                  Currency, TenderStatus
│   └── OpenApi/                Definición global de la documentación
├── tests/Unit · tests/Feature
└── bin/unspsc-to-sql.php       Generador de bd/02_activities.sql
```

**Sobre la "Vista" del MVC:** el frontend es una SPA independiente, así que el backend no renderiza HTML. La capa de vista está en `app/Http/Resources` (cómo se presentan los datos en JSON) y en `app/Views/Exports` (cómo se presenta el Excel). Los controladores nunca arman la respuesta a mano y los modelos no saben nada de HTTP.

Única dependencia de Laravel: `illuminate/database` (Eloquent standalone, permitido por la prueba). El router, el contenedor, el ciclo de request/response y el validador son propios.

Patrones usados: Front Controller, MVC, Dependency Injection, Repository, Service Layer, Form Request, Resource/Presenter, Strategy (reglas de validación), Value Objects/Enums.

### Frontend

```
frontend/src/
├── api/              Cliente Axios + un módulo por recurso (errores normalizados)
├── composables/      useTenderForm, useToasts, useDebouncedFn
├── components/base/  FormField, StatusBadge, AppModal, PaginationBar, EmptyState, ToastStack
├── components/tenders/  TenderForm, ActivityAutocomplete, DocumentModal, DocumentsSection
├── views/            Listado, creación, edición, detalle y 404
├── utils/            validators.js (mismas reglas y mensajes que la API), formatters.js
└── assets/scss/      Tema de Bootstrap con la identidad de Suplos
```

---

## API

Documentación interactiva: **http://localhost:8080/api/docs**. Se genera a partir de atributos `#[OA\...]` en el código, y una prueba falla si alguna ruta queda sin documentar.

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/tenders?consecutive=&subject=&description=&page=&per_page=` | Listado paginado y filtrado |
| GET | `/api/tenders/export?…` | Excel (generado en el backend) con los mismos filtros, sin paginación |
| POST | `/api/tenders` | Crear (JSON) |
| GET | `/api/tenders/{id}` | Detalle con actividad y documentos |
| PUT | `/api/tenders/{id}` | Editar + documentos (`multipart/form-data`; desde el navegador `POST` con `_method=PUT`) |
| GET | `/api/tenders/{id}/documents/{document}/download` | Descarga de un documento |
| GET | `/api/activities?search=&limit=` | Autocompletado de actividades (nombre o prefijo del código) |
| GET | `/api/activities/{id}` | Detalle de actividad |
| GET | `/api/health` | Estado del servicio |

Formato de error uniforme:

```json
{ "message": "Los datos enviados no son válidos.", "errors": { "subject": ["El campo objeto es obligatorio."] } }
```

---

## Reglas de negocio y validaciones

Cada regla se valida en el **frontend** (respuesta inmediata) y en el **backend** (fuente de verdad), con los mismos mensajes.

| Campo | Reglas |
|---|---|
| Objeto | Obligatorio, sin "solo espacios", máx. 150 (contador y bloqueo al escribir) |
| Descripción / Alcance | Obligatorio, sin "solo espacios", máx. 400 (contador y bloqueo) |
| Moneda | Obligatorio, solo `COP`, `USD`, `EUR` |
| Presupuesto | Obligatorio, > 0, solo números y punto, máx. 2 decimales (la interfaz elimina caracteres no numéricos) |
| Actividad | Obligatorio, debe existir en BD; buscador con Axios, deshabilitado hasta que carguen los datos |
| Fecha / hora de inicio | Obligatorias, `YYYY-MM-DD` y `HH:mm` (24 h) válidas |
| Fecha de cierre | No puede ser menor a la fecha de inicio |
| Hora de cierre | Si las fechas son iguales, debe ser mayor a la hora de inicio |
| Documentos (edición) | Al menos 1 (existentes + nuevos); título ≤ 100, descripción ≤ 200; **PDF o ZIP** verificados por contenido real (no solo por extensión), máx. 10 MB |

- **Botón Guardar:** se habilita solo cuando todo es válido. Los errores del servidor se muestran junto al campo correspondiente.
- **Consecutivo:** lo genera el backend con el formato `O-{número de 4 dígitos}-{año en 2 dígitos}`, por ejemplo `O-0001-26`. Se reinicia cada año y se reserva con un *upsert* atómico dentro de la transacción, así que dos solicitudes simultáneas nunca obtienen el mismo número. Lo que el cliente envíe como consecutivo o estado se ignora.
- **Estado:** se calcula a partir del cronograma. Es **Programada** antes del inicio, **Abierta** entre el inicio y el cierre, y **Cerrada** después del cierre. Se guarda en `status` al crear o editar y en cada respuesta se recalcula con la hora actual, para que nunca quede desactualizado.
- **Excel:** se genera en el servidor con PhpSpreadsheet, con los mismos filtros del listado y sin paginación.

---

## Seguridad

- Consultas solo con *bindings* de Eloquent/Query Builder; en los filtros `LIKE`, los comodines `%` y `_` se escapan.
- Los archivos se validan por **MIME real** (`finfo`), además de la extensión y el tamaño. Se guardan **fuera de `public/`** con nombres aleatorios y solo se descargan a través de la API, comprobando que el documento pertenezca a esa licitación.
- Las celdas del Excel se escriben como texto explícito, para evitar inyección de fórmulas.
- Sin trazas de error en producción (`APP_DEBUG=false`); los errores inesperados se registran en `storage/logs`.
- Cabeceras de seguridad (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) en Nginx y Apache, y versiones de servidor ocultas.
- Lista blanca de campos: solo se guardan los campos validados (no hay asignación masiva de `consecutive` ni `status`).
- Un JSON mal formado o con una codificación distinta de UTF-8 responde `400`; no se procesa como un cuerpo vacío.

---

## Flujo de trabajo con Git

- Ramas: `main` (releases etiquetados), `develop` (integración) y ramas cortas `feature/*`, `fix/*`, `test/*`, `chore/*` y `ci/*`, que se unen con `--no-ff`.
- Commits atómicos siguiendo [Conventional Commits](https://www.conventionalcommits.org/es/): `feat(tenders): …`, `test(core): …`, `fix(documents): …`.
- CI en cada push y pull request: lint, análisis estático, pruebas de backend con MySQL, pruebas de frontend, build y E2E sobre el stack de Docker.
