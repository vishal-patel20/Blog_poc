<?php

declare(strict_types=1);
namespace App\Patterns\Strategy;

final class PricingEngine {
    public function __construct(private DiscountStrategy $strategy) {}

    public function getPrice(float $price): float {
        return $this->strategy->calculate($price);
    }
}