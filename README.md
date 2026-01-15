# Laravel Query Gate Example

A complete blog API demonstrating [Laravel Query Gate](https://github.com/behindSolution/laravel-query-gate) features.

## Features

- **Posts, Comments, Categories, Tags** - Full CRUD with relationships
- **API Versioning** - 3 versions (2024-01-01, 2024-06-01, 2025-01-01)
- **Custom Actions** - Publish, Archive, Feature posts; Approve, Reject comments
- **Authentication** - Laravel Sanctum
- **Filters** - Advanced filtering with multiple operators
- **Repository & Service Pattern**

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL/PostgreSQL

## Installation

```bash
git clone git@github.com:behindSolution/LQG-example.git
cd LQG-example
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Running

```bash
php artisan serve
```

API available at `http://localhost:8000`

## Testing the API

### 1. Import Postman Collection

Import `postman_collection.json` into Postman.

### 2. Register/Login

```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

Save the `token` from response.

### 3. Use the API

```bash
# List posts (requires auth)
curl http://localhost:8000/query/posts \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create post
curl -X POST http://localhost:8000/query/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"My Post","content":"Content here"}'

# Filter posts by status
curl "http://localhost:8000/query/posts?filter[status][eq]=published" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Filter by tags
curl "http://localhost:8000/query/posts?filter[tag_slugs][in]=laravel,php" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Publish post (custom action)
curl -X POST http://localhost:8000/query/posts/1/publish \
  -H "Authorization: Bearer YOUR_TOKEN"

# List comments (public)
curl http://localhost:8000/query/comments

# Create anonymous comment
curl -X POST http://localhost:8000/query/comments \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"author_name":"John","author_email":"john@example.com","content":"Nice post!"}'
```

### 4. API Versioning

```bash
# Use specific version
curl http://localhost:8000/query/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Query-Version: 2024-01-01"

# View changelog
curl http://localhost:8000/query/posts/__changelog
```

## Endpoints

| Resource | Auth Required |
|----------|---------------|
| Posts | Yes (all operations) |
| Comments | No (list, create anonymous) / Yes (update, delete, moderate) |
| Categories | No |
| Tags | No |

## Documentation

OpenAPI docs available at `http://localhost:8000/query/docs`

## License

MIT
