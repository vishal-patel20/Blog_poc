<?php

declare(strict_types=1);
namespace App\Solid\Ocp;

class EmailNotifier implements NotifierInterface {
    public function send(string $message): void {
        // Send email
    }
}