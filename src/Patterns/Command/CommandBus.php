<?php

declare(strict_types=1);
namespace App\Patterns\Command;

class CommandBus {
    private array $history = [];

    public function execute(CommandInterface $command): void {
        $command->execute();
        $this->history[] = $command;
    }

    public function undoLast(): void {
        if (empty($this->history)) return;
        $command = array_pop($this->history);
        $command->undo();
    }
}