# Blog Open AI

A Laravel 12 **API-only** backend for a blog with **OpenAI-powered image prompt generation**. Users authenticate with Sanctum bearer tokens, manage their own posts, and upload images to receive detailed prompts suitable for AI image-generation tools (via GPT-4o vision).

## Features

- **Authentication** — Register, login (returns API token), logout, password reset, and email verification (Breeze API scaffolding)
- **Posts** — Full CRUD for blog posts scoped to the authenticated author
- **Image prompt generation** — Upload an image; OpenAI analyzes it and returns a descriptive recreation prompt
- **Generation history** — List past generations with optional search and sorting
- **API documentation** — OpenAPI spec via [Scramble](https://github.com/dedoc/scramble) (`api.json` export; interactive docs at `/docs/api` when the app is running)

## Tech stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 12 |
| PHP | 8.2+ |
| Auth | Laravel Sanctum (personal access tokens) |
| AI | [openai-php/client](https://github.com/openai-php/client) — GPT-4o (vision) |
| API docs | dedoc/scramble |
| Tests | Pest 3 |
| Database | SQLite by default (MySQL via `.env` or Docker Compose) |

## Project structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/          # Posts & image generations
│   │   └── Auth/            # Register, login, password reset, verification
│   ├── Requests/            # Validation (posts, image upload, login)
│   └── Resources/           # JSON API resources
├── Models/                  # User, Post, ImageGeneration
└── Services/
    └── OpenAiService.php    # Vision API integration
routes/
├── api.php                  # Protected v1 routes + auth includes
└── auth.php                 # Auth endpoints (included from api.php)
```

## Requirements

- PHP 8.2 or higher with common extensions (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`)
- [Composer](https://getcomposer.org/)
- An [OpenAI API key](https://platform.openai.com/api-keys)

**Or** [Docker](https://www.docker.com/) and Docker Compose (see [Docker](#docker-php--mysql--phpmyadmin) below).

## Installation

1. **Clone and install dependencies**

   ```bash
   composer install
   ```

2. **Environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Add your OpenAI key to `.env`:

   ```env
   OPENAI_API_KEY=sk-your-key-here
   ```

   Optional — if you use a separate frontend (password reset / email verification redirects):

   ```env
   FRONTEND_URL=http://localhost:3000
   ```

3. **Database**

   SQLite is the default. Create the database file if it does not exist:

   ```bash
   touch database/database.sqlite
   php artisan migrate
   ```

   For MySQL, uncomment and set the `DB_*` variables in `.env`, then run `php artisan migrate`.

4. **Public storage** (required for `image_url` in generation responses)

   ```bash
   php artisan storage:link
   ```

5. **Run the server**

   ```bash
   php artisan serve
   ```

   API base URL: `http://localhost:8000/api`

## Docker (PHP + MySQL + phpMyAdmin)

Run the full stack with [Docker Compose](https://docs.docker.com/compose/):

| Service | URL | Purpose |
|---------|-----|---------|
| Laravel (nginx + PHP 8.3) | http://localhost:8000 | API (`/api/...`) |
| phpMyAdmin | http://localhost:8080 | Database UI |
| MySQL | `localhost:3306` | Database server |

### Quick start

1. **Copy the Docker environment file**

   ```bash
   cp .env.docker.example .env
   ```

   Set `OPENAI_API_KEY` in `.env`.

2. **Build and start**

   ```bash
   docker compose up -d --build
   ```

   On first run, the `app` container will install Composer dependencies, generate an app key, wait for MySQL, run migrations, and create the storage symlink.

3. **Verify**

   ```bash
   curl http://localhost:8000/up
   ```

### Default credentials

| Setting | Value |
|---------|-------|
| Database | `blog` |
| DB user | `laravel` |
| DB password | `secret` |
| MySQL root password | `root` |

phpMyAdmin: log in as `root` / `root` or `laravel` / `secret`.

Override ports or passwords via `.env`:

```env
APP_PORT=8000
PHPMYADMIN_PORT=8080
MYSQL_PORT=3306
MYSQL_ROOT_PASSWORD=root
DB_DATABASE=blog
DB_USERNAME=laravel
DB_PASSWORD=secret
```

### Composer (no local install required)

You do **not** need Composer installed on Windows/macOS/Linux if you use Docker:

```bash
# Install / update PHP dependencies
docker compose run --rm composer install

# Or run inside the app container (after rebuild)
docker compose exec app /usr/local/bin/composer install
```

If you see `composer: command not found` on your **host**, use the commands above instead of `composer` directly, then rebuild the app image:

```bash
docker compose build --no-cache app
docker compose up -d
```

### Useful commands

```bash
# View logs
docker compose logs -f app

# Run Artisan
docker compose exec app php artisan migrate

# Stop containers
docker compose down

# Stop and remove database volume
docker compose down -v
```

### Files

- [`Dockerfile`](Dockerfile) — PHP 8.3-FPM with Laravel extensions
- [`docker-compose.yml`](docker-compose.yml) — `app`, `nginx`, `mysql`, `phpmyadmin`
- [`docker/nginx/default.conf`](docker/nginx/default.conf) — nginx site config
- [`docker/entrypoint.sh`](docker/entrypoint.sh) — startup (Composer, migrate, storage link)

## API overview

All routes below are prefixed with `/api`. Protected routes require:

```http
Authorization: Bearer {token}
```

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/register` | Create account (204 No Content) |
| `POST` | `/login` | Returns `{ user, token }` |
| `POST` | `/logout` | Revoke current token (auth required) |
| `POST` | `/forgot-password` | Send reset link |
| `POST` | `/reset-password` | Reset password with token |
| `GET` | `/verify-email/{id}/{hash}` | Verify email (signed URL) |
| `POST` | `/email/verification-notification` | Resend verification (auth required) |
| `GET` | `/user` | Current user (auth required) |

**Login example**

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

### Posts (`/api/v1/posts`)

Standard REST resource. Each user only sees and mutates their own posts (`author_id` check on show/update/delete).

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/v1/posts` | Paginated list (2 per page) |
| `POST` | `/v1/posts` | Create (`title`, `body`, optional `tags[]`) |
| `GET` | `/v1/posts/{post}` | Show single post |
| `PUT`/`PATCH` | `/v1/posts/{post}` | Update |
| `DELETE` | `/v1/posts/{post}` | Delete (204) |

### Image generations (`/api/v1/image-generations`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/v1/image-generations` | Paginated history |
| `POST` | `/v1/image-generations` | Upload image, generate prompt |

**Upload example**

```bash
curl -X POST http://localhost:8000/api/v1/image-generations \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "image=@/path/to/photo.jpg"
```

**List query parameters** (intended filters on the index endpoint):

- `search` — Filter by text in `generated_prompt`
- `sort` — Sort field (`created_at`, `generated_prompt`, `original_filename`, `file_size`); prefix with `-` for descending (e.g. `-created_at`)

Uploaded images are stored under `storage/app/public/uploads/images/`. Responses include a public `image_url` when storage is linked.

## OpenAPI documentation

- **Exported spec:** [`api.json`](api.json) at the project root
- **Interactive UI:** With the app running, visit `http://localhost:8000/docs/api` (Scramble default)
- Bearer auth is documented in the generated OpenAPI security scheme

## Configuration

| Variable | Purpose |
|----------|---------|
| `OPENAI_API_KEY` | OpenAI API authentication (`config/services.php`) |
| `APP_URL` | Base URL for generated asset links |
| `FRONTEND_URL` | Used in password-reset and email-verification redirect URLs |
| `DB_*` | Database connection (SQLite default) |
| `QUEUE_CONNECTION` | `database` in `.env.example` — use a queue worker if you add async jobs later |

Rate limiting: API routes use the `api` limiter (1000 requests/minute per user or IP), configured in `AppServiceProvider`.

## Testing

```bash
composer test
# or
php artisan test
```

Feature tests cover registration, login, logout, password reset, and email verification under `tests/Feature/Auth/`.

## How image prompt generation works

1. Client sends a multipart `POST` with an `image` file.
2. The file is validated and stored on the `public` disk.
3. `OpenAiService` encodes the image and calls OpenAI Chat Completions (`gpt-4o`) with a vision payload.
4. The model returns a detailed prompt; the app persists it in `image_generations` linked to the user.

See [`app/Services/OpenAiService.php`](app/Services/OpenAiService.php) for the integration details.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
