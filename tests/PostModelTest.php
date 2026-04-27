<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Post;

/**
 * Unit tests for the Post model.
 */
class PostModelTest extends BaseTestCase
{
    public function testPostHasCorrectDefaultStatus(): void
    {
        $post = new Post('Hello World', 'Body text');

        $this->assertSame('draft', $post->getStatus());
    }

    public function testPublishChangesStatusToPublished(): void
    {
        $post = new Post('Hello World', 'Body text');
        $post->publish();

        $this->assertSame('published', $post->getStatus());
    }

    public function testSoftDeleteSetsDeletedAt(): void
    {
        $post = new Post('Delete Me', 'Body');
        $post->softDelete();

        $this->assertNotNull($post->getDeletedAt());
        $this->assertTrue($post->isDeleted());
    }

    public function testToArrayContainsAllExpectedKeys(): void
    {
        $post = new Post('Title', 'Body', 'published');
        $post->setId(1);

        $arr = $post->toArray();

        $this->assertArrayHasKey('id', $arr);
        $this->assertArrayHasKey('title', $arr);
        $this->assertArrayHasKey('body', $arr);
        $this->assertArrayHasKey('status', $arr);
        $this->assertArrayHasKey('deleted_at', $arr);
        $this->assertArrayHasKey('created_at', $arr);
        $this->assertArrayHasKey('updated_at', $arr);
    }

    public function testFromRowHydratesPost(): void
    {
        $row = [
            'id'         => 42,
            'title'      => 'Test',
            'body'       => 'Some body',
            'status'     => 'published',
            'deleted_at' => null,
            'created_at' => '2026-01-01T00:00:00+00:00',
            'updated_at' => '2026-01-02T00:00:00+00:00',
        ];

        $post = Post::fromRow($row);

        $this->assertSame(42, $post->getId());
        $this->assertSame('Test', $post->getTitle());
        $this->assertSame('published', $post->getStatus());
    }
}
