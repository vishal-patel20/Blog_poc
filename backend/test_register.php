<?php
require 'vendor/autoload.php';

$dotenvPath = __DIR__;
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
    $dotenv->required(['JWT_SECRET'])->notEmpty();
}

$userRepo = new \App\Repositories\UserRepository();
$user = new \App\Models\User(
    name: 'test',
    email: 'test999@test.com',
    password: \App\Core\Auth::hashPassword('password123'),
    role: 'reader'
);

try {
    $id = $userRepo->save($user);
    echo "Success! User ID: $id\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}
