<?php

declare(strict_types=1);

namespace Tests;

use App\Exceptions\NotFoundException;
use App\Models\Post;
use App\Repositories\PostRepository;

/**
 * Unit tests for PostRepository using the in-memory SQLite database.
 */
class PostRepositoryTest extends BaseTestCase
{
    private PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostRepository();
    }

    public function testSaveAndFindById(): void
    {
        $post = new Post('First Post', 'Body of first post', 'draft');
        $id   = $this->repository->save($post);

        $this->assertGreaterThan(0, $id);

        $found = $this->repository->findById($id);

        $this->assertSame('First Post', $found->getTitle());
        $this->assertSame('Body of first post', $found->getBody());
    }

    public function testFindAllReturnsPaginatedResults(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->save(new Post("Post {$i}", "Body {$i}"));
        }

        $page1 = $this->repository->findAll(1, 3);
        $page2 = $this->repository->findAll(2, 3);

        $this->assertCount(3, $page1);
        $this->assertCount(2, $page2);
    }

    public function testSoftDeleteExcludesPostFromFindAll(): void
    {
        $post = new Post('To Delete', 'Body');
        $id   = $this->repository->save($post);

        $this->repository->delete($id);

        $posts = $this->repository->findAll();

        $this->assertCount(0, $posts);
    }

    public function testFindByIdThrowsNotFoundExceptionForSoftDeletedPost(): void
    {
        $this->expectException(NotFoundException::class);

        $post = new Post('To Delete', 'Body');
        $id   = $this->repository->save($post);
        $this->repository->delete($id);

        $this->repository->findById($id);
    }

    public function testUpdateChangesPostFields(): void
    {
        $post = new Post('Original', 'Old body');
        $id   = $this->repository->save($post);

        $loaded = $this->repository->findById($id);
        $loaded->setTitle('Updated Title');
        $loaded->setBody('New body');
        $this->repository->update($loaded);

        $updated = $this->repository->findById($id);

        $this->assertSame('Updated Title', $updated->getTitle());
        $this->assertSame('New body', $updated->getBody());
    }

    public function testCountReturnsCorrectTotal(): void
    {
        $this->repository->save(new Post('Post A', 'Body'));
        $this->repository->save(new Post('Post B', 'Body'));

        $this->assertSame(2, $this->repository->count());
    }
}
