# Blog REST API Implementation Plan

This plan details the process for building the custom REST API without a framework, strictly following the POC document.

## User Review Required

> [!IMPORTANT]
> The POC specifies using **MySQL or SQLite**. To minimize complex configuration and keep the project self-contained, I propose using **SQLite** as the database. Please confirm if SQLite is acceptable, or if you'd prefer to connect to an existing MySQL instance instead.

> [!WARNING]
> The workflow requires simulating PRs and feature branches (`feature/router`, `feature/post-crud`, etc.). Since I am interacting with your local directory, I will create these branches and make standard conventional commits to them. However, **you will need to manually create the Pull Requests on GitHub** and merge them to the `develop` (and eventually `main`) branch as part of your POC assessment.

## Proposed Changes

### 1. Environment & Architecture
- **Directory**: Set up scaffolding exactly matching the PDF (`src/Controllers`, `src/Models`, `src/Exceptions`, `src/Core`, `database/migrations/`, etc.).
- **PHP Setup**: Initialize `composer.json` using `php init` and configure PSR-4 autoloading for `App\` to map to `/src`. Install PHPUnit.
- **Database**: Initialize a simple `database.sqlite` and create migration scripts for the `posts` and `comments` tables.

### 2. Core Implementation (Custom Framework)
- #### [NEW] `src/Core/Router.php`
  - Routes URLs mapped to HTTP methods (`GET`, `POST`, `PUT`, `PATCH`, `DELETE`).
- #### [NEW] `src/Core/Database.php`
  - Singleton pattern implementing a single PDO connection to the SQLite database.
- #### [NEW] `src/Core/Request.php` & `src/Core/Response.php`
  - Encapsulate parameters and output standardized generic JSON API responses.

### 3. OOP & Features Integration
- #### [NEW] `src/Models/BaseModel.php`, `src/Models/Timestampable.php`
  - Utilize abstracts and traits to share common state logic (`created_at`, `updated_at`).
- #### [NEW] Models and Repositories
  - Create `Post` and `Comment` models conforming to `RepositoryInterface`.
  - Establish `PostRepository` implementing logic for soft deletes, paginated retrieval, and CRUD actions.
- #### [NEW] Controllers
  - Implement the 8 required REST API endpoints, routing logic, and HTTP 422 Validations (throwing custom `ValidationException`).

## Open Questions

1. Is **SQLite** an acceptable choice for this database?
2. Currently, the repository only has a `main` branch. Will you be manually creating a remote repository on GitHub right away, or should I just configure local feature branches for now?

## Verification Plan

### Automated Tests
- I will execute PHP CodeSniffer: `./vendor/bin/phpcs --standard=PSR12 src/`
- I will configure and run PHPUnit `./vendor/bin/phpunit tests/` and verify that at least 5 backend unit tests correctly pass.

### Manual Verification
- We will spin up a local built-in server via `php -S localhost:8000 -t public`.
- I can draft a Postman Collection (or you can use cURL commands) to hit each of the 8 endpoints and ensure they return the expected JSON and correct HTTP Status codes (200, 404, 422).
