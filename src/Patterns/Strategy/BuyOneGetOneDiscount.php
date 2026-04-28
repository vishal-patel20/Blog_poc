<?php
namespace App\Patterns\Strategy;

final class BuyOneGetOneDiscount implements DiscountStrategy {
    public function calculate(float $originalPrice): float {
        return $originalPrice / 2; // Simple representation of BOGO on a single item value
    }
}