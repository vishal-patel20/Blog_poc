<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Solid\Dip\PostController;
use App\Solid\Dip\InMemoryPostRepository;
use App\Solid\Ocp\NotificationService;
use App\Solid\Ocp\NotifierInterface;
use Mockery;

class SolidTest extends TestCase {
    public function test_dip_controller_uses_interface() {
        $repo = new InMemoryPostRepository();
        $controller = new PostController($repo);
        $this->assertCount(2, $controller->index());
    }

    public function test_ocp_notification_service_uses_mock() {
        $mockNotifier = Mockery::mock(NotifierInterface::class);
        $mockNotifier->shouldReceive('send')->once()->with('Hello');
        
        $service = new NotificationService();
        $service->notify($mockNotifier, 'Hello');
        $this->assertTrue(true); // Mockery assertions
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}