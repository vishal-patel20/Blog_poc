<?php
namespace App\Patterns\Strategy;

final class PercentageDiscount implements DiscountStrategy {
    public function __construct(private readonly float $percent) {}

    public function calculate(float $originalPrice): float {
        return $originalPrice * (1 - ($this->percent / 100));
    }
}