<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Exceptions\NotFoundException;

/**
 * AuthController — handles user registration, login, logout, and profile.
 *
 * Endpoints:
 *   POST /api/auth/register  — create account, returns JWT
 *   POST /api/auth/login     — verify credentials, returns JWT
 *   POST /api/auth/logout    — blacklist current token
 *   GET  /api/auth/me        — return authenticated user's profile
 */
class AuthController
{
    private UserRepository $users;

    public function __construct(private readonly ?object $currentUser)
    {
        $this->users = new UserRepository();
    }

    // ------------------------------------------------------------------
    // POST /api/auth/register
    // ------------------------------------------------------------------

    public function register(Request $request): void
    {
        $data   = $request->all();
        $errors = [];

        // --- Validate name ---
        if (empty(trim((string) ($data['name'] ?? '')))) {
            $errors['name'][] = 'The name field is required.';
        } elseif (mb_strlen((string) $data['name']) > 100) {
            $errors['name'][] = 'Name must not exceed 100 characters.';
        }

        // --- Validate email ---
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            $errors['email'][] = 'The email field is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'A valid email address is required.';
        } elseif ($this->users->emailExists($email)) {
            $errors['email'][] = 'This email is already registered.';
        }

        // --- Validate password ---
        $password = (string) ($data['password'] ?? '');
        if ($password === '') {
            $errors['password'][] = 'The password field is required.';
        } elseif (mb_strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'][] = 'Password must contain at least one letter and one number.';
        }

        // All new accounts default to 'author' so any registered user can create posts.
        $role = 'author';

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $user = new User(
            name:     trim((string) $data['name']),
            email:    $email,
            password: Auth::hashPassword($password),
            role:     $role,
        );

        $id = $this->users->save($user);

        // Auto login via session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $id;
        $_SESSION['email'] = $email;
        $_SESSION['name'] = $user->getName();
        $_SESSION['role'] = $role;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        Response::json([
            'message' => 'Account created successfully.',
            'user'    => $user->toArray(),
        ], 201);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/login
    // ------------------------------------------------------------------

    private function checkBruteForce(string $email): void
    {
        $file = __DIR__ . '/../../../database/brute_force.json';
        if (!file_exists($file)) file_put_contents($file, '{}');
        $data = json_decode(file_get_contents($file), true) ?? [];
        $now = time();

        if (isset($data[$email]) && $data[$email]['lock_until'] > $now) {
            Response::error('Account locked due to too many failed attempts. Try again later.', 429);
        }
    }

    private function recordFailedLogin(string $email): void
    {
        $file = __DIR__ . '/../../../database/brute_force.json';
        $data = json_decode(file_get_contents($file), true) ?? [];
        $now = time();

        if (!isset($data[$email]) || $data[$email]['lock_until'] <= $now) {
            $data[$email] = ['attempts' => 1, 'lock_until' => 0];
        } else {
            $data[$email]['attempts']++;
        }

        if ($data[$email]['attempts'] >= 5) {
            $data[$email]['lock_until'] = $now + 900; // 15 mins lock
        }

        file_put_contents($file, json_encode($data));
    }

    private function resetFailedLogin(string $email): void
    {
        $file = __DIR__ . '/../../../database/brute_force.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
            if (isset($data[$email])) {
                unset($data[$email]);
                file_put_contents($file, json_encode($data));
            }
        }
    }

    public function login(Request $request): void
    {
        $data   = $request->all();
        $errors = [];

        $email    = strtolower(trim((string) ($data['email']    ?? '')));
        $password = (string) ($data['password'] ?? '');

        if ($email === '')    { $errors['email'][]    = 'The email field is required.'; }
        if ($password === '') { $errors['password'][] = 'The password field is required.'; }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $this->checkBruteForce($email);

        $invalidMsg = 'Invalid email or password.';

        try {
            $user = $this->users->findByEmail($email);
        } catch (NotFoundException) {
            // Argon2ID dummy hash
            Auth::verifyPassword($password, '$argon2id$v=19$m=65536,t=4,p=1$ZHVtbXlzYWx0c3RyaW5nMTI$eX/2aD7Gf/2YpA7nZ+W1M/8wZ+6O5O3D9W5B2L7R1k0');
            $this->recordFailedLogin($email);
            Response::error($invalidMsg, 401);
        }

        if (!Auth::verifyPassword($password, $user->getPassword())) {
            $this->recordFailedLogin($email);
            Response::error($invalidMsg, 401);
        }

        $this->resetFailedLogin($email);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['name'] = $user->getName();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

        Response::json([
            'message' => 'Login successful.',
            'user'    => $user->toArray(),
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/logout
    // ------------------------------------------------------------------

    public function logout(Request $request): void
    {
        session_unset();
        session_destroy();
        Response::json(['message' => 'Logged out successfully.']);
    }

    // ------------------------------------------------------------------
    // GET /api/auth/me
    // ------------------------------------------------------------------

    public function me(Request $request): void
    {
        try {
            $user = $this->users->findById((int) $this->currentUser->sub);
        } catch (NotFoundException) {
            Response::error('User not found.', 404);
        }

        Response::json(['data' => $user->toArray()]);
    }
}
