<?php
namespace App\Solid\Dip;

/**
 * Dependency Inversion Principle (DIP)
 * Problem it solves: High-level modules should not depend on low-level modules. Both should depend on abstractions.
 * Why chosen: PostController depends on PostRepositoryInterface, making it easy to swap PDO for InMemory in testing.
 * What breaks if removed: PostController would be tightly coupled to a concrete DB class, making unit testing impossible without a DB.
 */
class PostController {
    public function __construct(private PostRepositoryInterface $repository) {}

    public function index(): array {
        return $this->repository->getPosts();
    }
}