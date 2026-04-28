<?php

declare(strict_types=1);
namespace App\Patterns\Strategy;

final class FixedAmountDiscount implements DiscountStrategy {
    public function __construct(private readonly float $amount) {}

    public function calculate(float $originalPrice): float {
        return max(0, $originalPrice - $this->amount);
    }
}