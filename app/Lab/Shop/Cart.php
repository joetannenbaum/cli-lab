<?php

namespace App\Lab\Shop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Cart
{
    public string $cartKey;

    public function __construct(public array $items = [])
    {
        //
    }

    public function add(Product $product, int $quantity = 1): void
    {
        if (isset($this->items[$product->id])) {
            $this->items[$product->id]['quantity'] += $quantity;
        } else {
            $this->items[$product->id] = [
                'product' => $product->toArray(),
                'quantity' => $quantity,
            ];
        }
    }

    public function remove(Product $product, int $quantity = 1): void
    {
        if (isset($this->items[$product->id])) {
            $this->items[$product->id]['quantity'] -= $quantity;

            if ($this->items[$product->id]['quantity'] <= 0) {
                unset($this->items[$product->id]);
            }
        }
    }

    public function total(): float
    {
        return collect($this->items)
            ->map(fn ($item) => $item['product']['price'] * $item['quantity'])
            ->sum();
    }

    public function count(): int
    {
        return collect($this->items)
            ->pluck('quantity')
            ->sum();
    }

    public function checkout(): void
    {
        if (!isset($this->cartKey)) {
            do {
                $cacheKey = strtoupper(Str::random(10));
            } while (Cache::has('shop:cart:' . $cacheKey));

            $this->cartKey = $cacheKey;
        }

        Cache::put('shop:cart:' . $this->cartKey, $this->items, now()->addHour());
    }
}
