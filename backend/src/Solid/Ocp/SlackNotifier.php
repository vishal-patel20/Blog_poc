<?php

declare(strict_types=1);
namespace App\Solid\Ocp;

class SlackNotifier implements NotifierInterface {
    public function send(string $message): void {
        // Send slack message
    }
}