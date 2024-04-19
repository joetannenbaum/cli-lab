<?php

namespace App\Lab\Shop;

class Product
{
    public string $category;

    public function __construct(public string $id, public string $name, public string $description, public $price)
    {
        //
    }

    public static function make(string $id, string $name, string $description, $price): static
    {
        return new static($id, $name, $description, $price);
    }

    public function category(string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'category' => $this->category,
        ];
    }
}
