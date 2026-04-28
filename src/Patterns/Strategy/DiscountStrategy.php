<?php
namespace App\Patterns\Strategy;

/**
 * Strategy Pattern
 * Problem it solves: We need to support multiple discount types at runtime and easily add new ones.
 * Why chosen: Extracts discount algorithms into separate classes, adhering to Open/Closed Principle.
 * What breaks if removed: PricingEngine would become a massive switch statement, hard to maintain and test.
 */
interface DiscountStrategy {
    public function calculate(float $originalPrice): float;
}