# VOLTERRA API

Laravel REST API for the [VOLTERRA](https://github.com/ahmedehab96-c/volterra) front end: the model range, saved configurations and information requests. MySQL storage. Public read and submit endpoints are rate limited; the admin API uses Sanctum bearer tokens for `admin` and `editor` roles.

## Setup

```bash
composer install
cp .env.example .env && php artisan key:generate
# set DB_* in .env, create the database, then:
# set ADMIN_EMAIL / ADMIN_PASSWORD in .env to create the first admin, then:
php artisan migrate --seed
php artisan serve            # http://127.0.0.1:8000
```

`CORS_ALLOWED_ORIGINS` (comma-separated) lists the front-end origins allowed to call the API. Localhost:5173 is the default; add your production domain there.

## Endpoints

| Method | Path | Notes |
| ------ | ---- | ----- |
| GET | `/api/models` | Active range with `gallery` image URLs and optional `model_3d` (cached) |
| GET | `/api/models/{slug}` | One model, `404` if unknown |
| GET | `/api/options` | Active configurator options (`id`, `type`, `code`, `name`, `price`) |
| POST | `/api/configurations/calculate` | `model_id`, `exterior_color_id`, `wheel_id`, `interior_id`. Returns `base_price`, `options_total`, `estimated_price` |
| POST | `/api/configurations` | `model`, `exterior_color`, `wheels`, `interior`. Validated and priced on the server, then saved |
| POST | `/api/inquiries` | `name`, `email`, `phone`, `country`, `message?`, plus the configuration fields |

### Admin (Bearer token, role `admin` or `editor`)

| Method | Path | Notes |
| ------ | ---- | ----- |
| POST | `/api/login` | `email`, `password`. Returns `token` and `user` (staff only, 5 attempts per minute) |
| POST | `/api/logout` | Revokes the current token |
| GET | `/api/user` | The signed-in user (`id`, `name`, `email`, `role`) |
| GET | `/api/admin/dashboard` | Total and active models, total and new inquiries |
| GET, POST, PUT, DELETE | `/api/admin/models[/{id}]` | Model CRUD. `PUT` accepts partial updates, e.g. `{ "status": "inactive" }` |
| GET, POST, PUT, DELETE | `/api/admin/options[/{id}]` | Configurator options. Filter with `?type=exterior_color\|wheels\|interior` |
| GET | `/api/admin/inquiries` | `q`, `status`, `model`, `page`. Newest first, 15 per page |
| GET, PATCH | `/api/admin/inquiries/{id}` | `PATCH` with `status` (`new\|contacted\|closed`) and/or `internal_notes` |
| GET, POST | `/api/admin/models/{id}/images` | Multipart upload: `image` (JPEG/PNG/WebP, up to 8 MB) and `type` (`hero\|exterior\|interior\|detail`) |
| PUT | `/api/admin/models/{id}/images/order` | `{ "ids": [...] }` in the new order |
| DELETE | `/api/admin/images/{id}` | Removes the record and the file |

Roles are stored in the database only. They are never accepted from a request, and the API never returns passwords or tokens.

**Storage and media.** Uploads go to `storage/app/public/vehicles/{model_id}/` with random names. Run `php artisan storage:link` once. Image URLs use `APP_URL`.

**Pricing.** `App\Support\Pricing` is the single source of truth. Saved configurations and inquiries are validated against active models and options and priced on the server. Client-sent prices, names and statuses are ignored.

**Notifications.** A new inquiry emails `ADMIN_EMAIL` through the `MAIL_*` settings after the response is sent. Mail failures are logged and never fail the request.

**Caching.** The public models and options are cached (`CATALOG_CACHE_TTL`). Model, image and option changes clear the cache immediately. Inquiries and admin data are never cached.

**Records.** Models are soft-deleted. Inquiries keep their model link and are never deleted automatically.

**Rate limits.** Saving configurations and inquiries: 20 per minute per IP. Price calculation: 120 per minute. Login: 5 per minute per account and IP.

Responses: `{ "success": true, "data": … }`. Errors: `{ "success": false, "message": "…", "errors": { field: [messages] } }` (422 for validation, 404, 429). 

Allowed options and their prices live in the `configuration_options` table, which admins manage. Only active models and options are accepted, and the estimate is always computed on the server.

## Tests

```bash
php artisan test
```
