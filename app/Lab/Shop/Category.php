<?php

namespace App\Lab\Shop;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Category
{
    public Collection $products;

    public int $selectedProduct = 0;

    public function __construct(public string $name, public string $color)
    {
        //
    }

    public static function make(string $name, string $color): static
    {
        return new static($name, $color);
    }

    public function products(array $products): static
    {
        $this->products = collect($products)->map(fn ($product) => $product->category(Str::singular($this->name)));

        return $this;
    }
}
