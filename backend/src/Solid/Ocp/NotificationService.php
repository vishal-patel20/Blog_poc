<?php

declare(strict_types=1);
namespace App\Solid\Ocp;

/**
 * Open/Closed Principle (OCP)
 * Problem it solves: Software entities should be open for extension, but closed for modification.
 * Why chosen: We can add new notification channels (Slack, SMS) by implementing NotifierInterface without altering this class.
 * What breaks if removed: Adding a new channel would require modifying the NotificationService (e.g., adding a switch statement).
 */
class NotificationService {
    public function notify(NotifierInterface $notifier, string $message): void {
        $notifier->send($message);
    }
}