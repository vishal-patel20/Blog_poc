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

        // --- Fix: Prevent Mass Assignment / Privilege Escalation ---
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

        $id    = $this->users->save($user);
        $token = Auth::generateToken($id, $email, $user->getName(), $role);

        Response::json([
            'message' => 'Account created successfully.',
            'token'   => $token,
            'user'    => $user->toArray(),
        ], 201);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/login
    // ------------------------------------------------------------------

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

        // Use a generic error to prevent user enumeration
        $invalidMsg = 'Invalid email or password.';

        try {
            $user = $this->users->findByEmail($email);
        } catch (NotFoundException) {
            // Perform dummy verify to maintain constant time
            Auth::verifyPassword($password, '$2y$12$dummyhashfortimingprotectionXXXXXXXXXXXXXXXXXXXXXXX');
            Response::error($invalidMsg, 401);
        }

        if (!Auth::verifyPassword($password, $user->getPassword())) {
            Response::error($invalidMsg, 401);
        }

        $token = Auth::generateToken(
            (int) $user->getId(),
            $user->getEmail(),
            $user->getName(),
            $user->getRole()
        );

        Response::json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => $user->toArray(),
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/auth/logout
    // ------------------------------------------------------------------

    public function logout(Request $request): void
    {
        $authHeader = $request->header('Authorization', '');
        $token      = Auth::extractBearer((string) $authHeader);

        if ($token !== '') {
            $decoded = $this->currentUser; // already validated in middleware
            Auth::blacklistToken($token, (int) ($decoded->exp ?? time() + Auth::TTL));
        }

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
