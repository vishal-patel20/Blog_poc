<?php
namespace App\Patterns\Command;

class SendEmailCommand implements CommandInterface {
    private bool $executed = false;
    public function execute(): void { $this->executed = true; }
    public function undo(): void { $this->executed = false; }
    public function isExecuted(): bool { return $this->executed; }
}