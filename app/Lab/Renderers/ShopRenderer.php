<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\HasMinimumDimensions;
use App\Lab\Shop;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Shop\Category;
use Chewie\Concerns\CapturesOutput;
use Chewie\Output\Lines;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class ShopRenderer extends Renderer
{
    use Aligns;
    use DrawsAscii;
    use HasMinimumDimensions;
    use DrawsHotkeys;
    use CapturesOutput;
    use DrawsBoxes;

    protected int $width;

    protected int $height;

    protected int $longestProductName;

    public function __invoke(Shop $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderShop($prompt), 70, 30);
    }

    protected function renderShop(Shop $prompt): self
    {
        $this->width = $prompt->terminal()->cols() - 2;
        $this->height = $prompt->terminal()->lines() - 6;

        if ($prompt->state === 'checkout') {
            $lines = [
                $this->bold($this->magenta('Thanks for shopping with us!')),
                '',
                'Your total is ' . $this->bold('$' . number_format($prompt->cart->total(), 2)),
                '',
                'You can checkout here:',
                '',
                $this->bold($this->underline($this->cyan(url('/shop/checkout/' . $prompt->cart->cartKey)))),
            ];

            $this->center($lines, $this->width, $this->height)->each($this->line(...));

            return $this;
        }

        $this->longestProductName = $prompt->categories
            ->map(
                fn (Category $category) => $category->products
                    ->pluck('name')
                    ->max(fn ($name) => mb_strwidth($name))
            )
            ->flatten()
            ->max() + 2;

        $this->renderCategories($prompt);
        $this->newLine();
        $this->renderProducts($prompt);

        while (substr_count($this->output, PHP_EOL) < $this->height - 1) {
            $this->newLine();
        }

        $this->hotkey('← →', 'Categories');
        $this->hotkey('↑ ↓', 'Products');
        $this->hotkey('c', 'Checkout', $prompt->cart->count() > 0);
        $this->hotkey('q', 'Quit');

        $this->centerHorizontally($this->hotkeys(), $this->width)->each($this->line(...));

        return $this;
    }

    protected function renderCategories(Shop $prompt): void
    {
        $categories = $prompt->categories->map(function (Category $category, $index) use ($prompt) {
            $selected = $index === $prompt->selectedCategory;

            if ($selected) {
                return $this->underline($this->{$category->color}($category->name));
            }

            return $this->dim($category->name);
        });

        $cart = $this->dim('Cart ')
            . $this->bold('$' . number_format($prompt->cart->total(), 2))
            . $this->dim(' (' . $prompt->cart->count() . ')');

        $cats = $categories->implode(str_repeat(' ', 4));

        $this->box('', $this->spaceBetween($this->width - 4, $cats, $cart));
    }

    protected function renderProducts(Shop $prompt): void
    {
        $category = $prompt->categories->get($prompt->selectedCategory);

        $colSpacing = 4;

        $productsNav = $category->products
            ->map(fn ($product) => mb_str_pad(' ' . $product->name . ' ', $this->longestProductName))
            ->map(function ($name, $index) use ($category) {
                $selected = $index === $category->selectedProduct;

                $color = 'bg' . ucwords($category->color);

                if ($selected) {
                    return '  ' . $this->{$color}($name);
                }

                return '  ' . $name;
            });

        $product = $category->products->get($category->selectedProduct);

        $detailWidth = min($this->width - $colSpacing - $this->longestProductName, 60);

        $description = explode(PHP_EOL, wordwrap($product->description, $detailWidth));

        while (count($description) < 6) {
            $description[] = '';
        }

        $this->minWidth = strlen('Press A to add to cart');

        $box = $this->captureOutput(function () use ($category, $prompt) {
            $this->box(
                title: '',
                body: $prompt->state === 'added' ? 'Added!' : 'Press ' . $this->bold('A') . ' to add to cart',
                color: $category->color,
            );
        });

        $box = collect(explode(PHP_EOL, $box))->map(
            fn ($line) => str_replace(
                [' │', ' ┌', ' └'],
                ['│', '┌', '└'],
                $line,
            ),
        )->filter();

        $detail = collect([
            $this->bold($product->name),
            $this->dim('$' . number_format($product->price, 2)),
            '',
        ])->merge($description)->merge([''])->merge($box);

        Lines::fromColumns([$productsNav, $detail])
            ->spacing($colSpacing)
            ->lines()
            ->each($this->line(...));
    }
}
