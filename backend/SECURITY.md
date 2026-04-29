# SECURITY.md

## Security Controls and Hardening

This document outlines the security controls implemented in the hardened Authentication and Authorization module for the Blog REST API.

### 1. Password Handling
Passwords are never stored in plaintext. The system uses PHP's native `password_hash()` API. 
Currently configured to use the strong **bcrypt** algorithm with a cost factor of `12`, ensuring resistance against brute-force and dictionary attacks. Password hashes are never exposed in API responses (specifically stripped out of the `toArray()` method of the User model).

### 2. Authentication & Session Security (JWT)
The application has migrated away from traditional cookie-based sessions to a modern **JSON Web Token (JWT)** architecture using `firebase/php-jwt`.
* **Statelessness:** No session fixation vulnerabilities.
* **Token Blacklisting:** When a user logs out, the JWT hash is permanently added to an SQLite-backed `token_blacklist` table. This provides immediate, secure token revocation capabilities, a common weakness in standard JWT implementations.
* **Timing Attack Prevention:** The login endpoint performs a dummy hash calculation if a user is not found, ensuring the response time remains constant whether the user exists or not (preventing username enumeration).

### 3. CSRF Protection
As a stateless REST API, the system is immune to Cross-Site Request Forgery (CSRF) by design. 
Tokens are managed entirely by the JavaScript client and sent via the `Authorization: Bearer <token>` header, rather than relying on browser-automatically-attached cookies.

### 4. Injection Prevention
* **SQL Injection (SQLi):** 100% of database queries utilize **PDO Prepared Statements**. No dynamic data is ever concatenated into SQL strings.
* **Cross-Site Scripting (XSS):** 
    - The backend enforces `application/json` Content-Type globally.
    - JSON serialization utilizes strict flags (`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`) to neutralize any HTML payloads before they reach the client.
    - The frontend JavaScript strictly uses an `escapeHtml()` utility to sanitize all user-generated content and API error messages before DOM insertion.

### 5. Rate Limiting (Brute Force / DoS Protection)
A proxy-aware, atomic File-based Rate Limiter guards the API.
* **Algorithm:** Sliding window counting. Limit is strictly **60 requests per 60 seconds**.
* **Race Condition Safe:** Uses exclusive file locking (`flock(LOCK_EX)`) to guarantee atomic read-modify-write cycles, preventing TOCTOU bypasses.
* **Fail-Closed:** If the rate limiter encounters filesystem errors, it safely rejects requests with a 503 error rather than failing open.

### 6. Security Headers
The API explicitly emits strict HTTP security headers on every response:
* `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'` (Ensures the API cannot execute scripts or be framed).
* `X-Content-Type-Options: nosniff` (Prevents MIME-sniffing).
* `Referrer-Policy: no-referrer`
* `Permissions-Policy: geolocation=(), microphone=(), camera=()`
* **CORS Restrictions:** A strict CORS policy is enforced, only allowing configured origins via the `.env` file instead of `*` wildcards.

### 7. Error Handling & Exception Masking
The application uses a custom global exception handler. 
Under no circumstances are stack traces, database schema details, or absolute file paths exposed to the client. All uncaught exceptions are trapped, logged securely to the internal server log, and masked behind a generic `500 Internal Server Error` JSON response.

### 8. Role-Based Access Control (RBAC) & IDOR Protection
Granular endpoint protection enforces both authentication and authorization:
* Users are strictly assigned roles (`admin`, `author`, `reader`).
* Resource modification (`PUT`, `PATCH`, `DELETE`) is guarded by rigorous ownership checks. A user can only modify resources where the `author_id` precisely matches their JWT Subject (`sub`), unless they hold the `admin` role.
