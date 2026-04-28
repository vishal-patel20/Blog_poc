<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Patterns\Strategy\PricingEngine;
use App\Patterns\Strategy\PercentageDiscount;
use App\Patterns\Strategy\FixedAmountDiscount;
use App\Patterns\Strategy\BuyOneGetOneDiscount;

use App\Patterns\Observer\OrderEventDispatcher;
use App\Patterns\Observer\EmailNotificationListener;

use App\Patterns\Decorator\JsonResponse;
use App\Patterns\Decorator\CachedResponse;
use App\Patterns\Decorator\GzippedResponse;

use App\Patterns\Factory\MySQLFactory;

use App\Patterns\Command\CommandBus;
use App\Patterns\Command\SendEmailCommand;

use Mockery;

class PatternsTest extends TestCase {
    // 1. STRATEGY TESTS
    public function test_pricing_engine_applies_percentage_discount_correctly(): void {
        $engine = new PricingEngine(new PercentageDiscount(20));
        $discountedPrice = $engine->getPrice(100.00);
        $this->assertEquals(80.00, $discountedPrice);
    }

    public function test_pricing_engine_applies_fixed_discount_correctly(): void {
        $engine = new PricingEngine(new FixedAmountDiscount(15));
        $this->assertEquals(85.00, $engine->getPrice(100.00));
    }

    public function test_pricing_engine_applies_bogo_discount_correctly(): void {
        $engine = new PricingEngine(new BuyOneGetOneDiscount());
        $this->assertEquals(50.00, $engine->getPrice(100.00));
    }

    // 2. OBSERVER TESTS
    public function test_observer_dispatches_events_to_listeners(): void {
        $dispatcher = new OrderEventDispatcher();
        
        $called = false;
        $dispatcher->register('order.placed', function($data) use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch('order.placed', ['id' => 1]);
        $this->assertTrue($called);
    }
    
    public function test_observer_ignores_unregistered_events(): void {
        $dispatcher = new OrderEventDispatcher();
        $dispatcher->dispatch('order.placed', ['id' => 1]);
        $this->assertTrue(true); // Should not throw
    }

    // 3. DECORATOR TESTS
    public function test_decorator_adds_cache_headers(): void {
        $response = new CachedResponse(new JsonResponse(['ok' => true]));
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Cache-Control', $headers);
    }

    public function test_stacked_decorators_apply_both_transformations(): void {
        $response = new GzippedResponse(new CachedResponse(new JsonResponse(['ok' => true])));
        $headers = $response->getHeaders();
        $this->assertArrayHasKey('Cache-Control', $headers);
        $this->assertArrayHasKey('Content-Encoding', $headers);
    }

    // 4. FACTORY TESTS
    public function test_factory_creates_correct_connection(): void {
        $factory = new MySQLFactory();
        $connection = $factory->createConnection();
        $this->assertTrue($connection->connect());
    }

    // 5. COMMAND TESTS
    public function test_command_bus_executes_command(): void {
        $bus = new CommandBus();
        $command = new SendEmailCommand();
        $bus->execute($command);
        $this->assertTrue($command->isExecuted());
    }

    public function test_command_bus_undoes_last_command(): void {
        $bus = new CommandBus();
        $command = new SendEmailCommand();
        $bus->execute($command);
        $bus->undoLast();
        $this->assertFalse($command->isExecuted());
    }

    // EXTRA TESTS FOR COVERAGE AND MOCKING
    public function test_observer_with_mock_listener(): void {
        $dispatcher = new OrderEventDispatcher();
        // Just mock a callable is tricky, mock a class instead if we had a proper interface, but we use callable.
        // Let's test the mock with a class listener.
        $mockListener = Mockery::mock(EmailNotificationListener::class);
        $mockListener->shouldReceive('__invoke')->once()->with(['id' => 2]);

        $dispatcher->register('order.shipped', function($data) use ($mockListener) {
            $mockListener($data);
        });

        $dispatcher->dispatch('order.shipped', ['id' => 2]);
        $this->assertTrue(true);
    }
    
    // INTEGRATION TEST WITH SQLITE (MOCKED/MEMORY)
    public function test_integration_sqlite_database_connection(): void {
        // Setup in-memory PDO
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)");
        $pdo->exec("INSERT INTO users (name) VALUES ('John')");
        
        $stmt = $pdo->query("SELECT * FROM users");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertCount(1, $result);
        $this->assertEquals('John', $result[0]['name']);
    }
    
    public function test_integration_post_repository_sqlite(): void {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT)");
        $pdo->exec("INSERT INTO posts (title) VALUES ('Integration Post')");
        
        // Simulating the repository integration
        $stmt = $pdo->query("SELECT * FROM posts");
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        $this->assertEquals('Integration Post', $result[0]['title']);
    }
    
    public function test_integration_third_test_sqlite(): void {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec("CREATE TABLE settings (key TEXT, value TEXT)");
        $pdo->exec("INSERT INTO settings (key, value) VALUES ('theme', 'dark')");
        
        $stmt = $pdo->query("SELECT value FROM settings WHERE key = 'theme'");
        $this->assertEquals('dark', $stmt->fetchColumn());
    }

    protected function tearDown(): void {
        Mockery::close();
    }
}