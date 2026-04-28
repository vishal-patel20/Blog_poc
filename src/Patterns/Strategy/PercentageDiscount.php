<?php

declare(strict_types=1);
namespace App\Patterns\Strategy;

final class PercentageDiscount implements DiscountStrategy {
    public function __construct(private readonly float $percent)
    {
        // Security Fix #20: Reject invalid percentage values.
        // A negative percent would INCREASE the price (business logic attack).
        // A percent > 100 would produce a negative price.
        if ($percent < 0 || $percent > 100) {
            throw new \InvalidArgumentException(
                "Discount percentage must be between 0 and 100, got {$percent}."
            );
        }
    }

    public function calculate(float $originalPrice): float {
        return $originalPrice * (1 - ($this->percent / 100));
    }
}