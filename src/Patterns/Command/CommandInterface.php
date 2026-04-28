<?php
namespace App\Patterns\Command;

/**
 * Command Pattern
 * Problem it solves: Need to queue background jobs (emails, reports) and potentially undo them.
 * Why chosen: Encapsulates all information needed to perform an action into a single object, allowing queueing and undo.
 * What breaks if removed: We couldn't easily queue jobs to be executed later or cleanly implement an undo stack.
 */
interface CommandInterface {
    public function execute(): void;
    public function undo(): void;
}