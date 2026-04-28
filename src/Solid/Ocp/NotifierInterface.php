<?php

declare(strict_types=1);
namespace App\Solid\Ocp;

interface NotifierInterface {
    public function send(string $message): void;
}