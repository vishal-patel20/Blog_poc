<?php

declare(strict_types=1);
namespace App\Solid\Isp;

/**
 * Interface Segregation Principle (ISP)
 * Problem it solves: Clients should not be forced to depend upon interfaces that they do not use.
 * Why chosen: Splitting a large RepositoryInterface into Readable and Writable prevents ReadOnlyRepository from having empty write methods.
 * What breaks if removed: ReadOnly classes would have to implement write methods that throw exceptions, violating LSP.
 */
class ReadOnlyRepository implements ReadableInterface {
    public function read(int $id): array {
        return ['id' => $id, 'data' => 'read only'];
    }
}