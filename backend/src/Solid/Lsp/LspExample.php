<?php

declare(strict_types=1);
namespace App\Solid\Lsp;
use App\Solid\Ocp\NotifierInterface;
use App\Solid\Ocp\EmailNotifier;
use App\Solid\Ocp\SlackNotifier;

/**
 * Liskov Substitution Principle (LSP)
 * Problem it solves: Derived classes must be substitutable for their base classes.
 * Why chosen: Any NotifierInterface implementation can be swapped without the calling code breaking.
 * What breaks if removed: A subclass might throw unexpected exceptions or change return types, breaking the consumer code.
 */
class LspExample {
    public function run(): void {
        // Both implementations can be substituted seamlessly
        $this->process(new EmailNotifier());
        $this->process(new SlackNotifier());
    }

    private function process(NotifierInterface $notifier): void {
        $notifier->send("Test");
    }
}