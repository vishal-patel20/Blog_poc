# ARCHITECTURE.md

## Pattern: Strategy - Discount Calculation

### Context
The system needs to support multiple discount types that can be applied to products at runtime. New discount types will be added as the business grows.

### Decision
Implemented the Strategy pattern with a \`DiscountStrategy\` interface. Each discount type is its own class. \`PricingEngine\` accepts any \`DiscountStrategy\` via constructor injection.

### Consequences
Positive: Adding a new discount type requires only a new class - no existing code changes. Each strategy is independently testable.
Negative: Small overhead of additional classes for simple discounts.

### Alternatives Considered
A switch/match statement inside \`PricingEngine\` was rejected because it would violate the Open/Closed principle and grow unboundedly with new types.

---

## Pattern: Observer - Order Lifecycle Events

### Context
When an order is placed, shipped, or cancelled, multiple secondary actions need to trigger (e.g., email notification, inventory update, audit logging).

### Decision
Implemented the Observer pattern with an \`OrderEventDispatcher\`. Listeners dynamically register to events. The dispatcher loops through registered callables.

### Consequences
Positive: Adding a new secondary action (listener) requires zero changes to the order processing logic. 
Negative: Flow of control becomes less obvious because event execution is decoupled.

### Alternatives Considered
Hardcoding the listener calls in the checkout service. Rejected because it violates the Single Responsibility and Open/Closed principles.

---

## Pattern: Decorator - HTTP Response Formatting

### Context
HTTP responses sometimes need caching headers, and sometimes need to be Gzipped. Creating a subclass for every combination is unsustainable.

### Decision
Implemented the Decorator pattern with a \`ResponseInterface\`. \`JsonResponse\` is the base class. \`CachedResponse\` and \`GzippedResponse\` wrap the response to add headers/modify the body dynamically.

### Consequences
Positive: Behaviors can be stacked dynamically at runtime (\`GzippedResponse(CachedResponse(...))\`).
Negative: Can lead to deep nesting of decorators making debugging slightly more difficult.

### Alternatives Considered
Multiple inheritance or a complex trait system. Rejected because PHP doesn't support multiple inheritance, and traits don't offer dynamic runtime composition.

---

## Pattern: Factory Method - Database Driver Abstraction

### Context
The application needs to connect to different databases (MySQL, SQLite, PostgreSQL) based on environment configuration, without coupling calling code to specific drivers.

### Decision
Implemented the Factory Method pattern. \`DatabaseFactory\` is the abstract creator, with concrete factories like \`MySQLFactory\`. They produce \`ConnectionInterface\` objects.

### Consequences
Positive: Client code is decoupled from concrete connection instantiation.
Negative: Requires creating a factory class for every connection type.

### Alternatives Considered
A simple factory function with a switch statement. Rejected because adding a new driver would require modifying the factory function (violates Open/Closed Principle).

---

## Pattern: Command - Background Job Queue

### Context
We need to queue jobs like sending emails and generating reports to execute later, and potentially we need to undo certain actions.

### Decision
Implemented the Command pattern with a \`CommandInterface\` (\`execute()\`, \`undo()\`). A \`CommandBus\` manages execution and keeps a history for the undo functionality.

### Consequences
Positive: Actions are encapsulated as objects, allowing easy queuing, serialization, and undo operations.
Negative: Every distinct action requires its own class, increasing the number of small classes.

### Alternatives Considered
Direct method calls on services. Rejected because it lacks the ability to easily queue actions or maintain a generic undo history.