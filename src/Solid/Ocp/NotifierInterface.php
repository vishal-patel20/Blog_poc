<?php
namespace App\Solid\Ocp;

interface NotifierInterface {
    public function send(string $message): void;
}