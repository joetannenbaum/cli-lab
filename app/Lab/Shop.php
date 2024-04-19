<?php

namespace App\Lab;

use App\Lab\Renderers\ShopRenderer;
use App\Lab\Shop\Cart;
use App\Lab\Shop\Category;
use App\Lab\Shop\Product;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;

class Shop extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;

    public Collection $categories;

    public Cart $cart;

    public int $selectedCategory = 0;

    protected KeyPressListener $listener;

    public function __construct()
    {
        $this->registerRenderer(ShopRenderer::class);

        $this->createAltScreen();

        $this->categories = collect([
            Category::make('Shirts', 'cyan')->products([
                Product::make(
                    Str::random(),
                    'Galactic Vibes',
                    'Embrace the cosmic allure with our Galactic Vibes Tees. Featuring intricate celestial designs and vibrant colors, these tees take your style to interstellar heights.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Neon Dream Dwellers',
                    'Step into a neon wonderland with the Neon Dream Dwellers Shirt. Its mesmerizing neon hues and abstract patterns create a futuristic and electrifying vibe.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Pixelated Paradise Top',
                    'Experience the nostalgia of the 8-bit era with the Pixelated Paradise Top. Its retro pixel art design and vibrant colors will transport you back to the golden age of gaming.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Cyber Sunset',
                    'Watch the sun set in a neon cyberpunk city with the Cyber Sunset Shirt. Its futuristic cityscape design and vibrant colors create a mesmerizing and surreal scene.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Jungle Mirage',
                    'Get lost in the lush and vibrant Jungle Mirage Shirt. Its tropical jungle design and vivid colors create a mesmerizing and immersive experience that will transport you to a tropical paradise.',
                    fake()->randomFloat(2, 10, 100),
                ),
            ]),
            Category::make('Mugs', 'red')->products([
                Product::make(
                    Str::random(),
                    'Quantum Elixir',
                    'Sip on the Quantum Elixir Mug and experience the power of the cosmos. Its intricate celestial design and vibrant colors create a mesmerizing and otherworldly experience.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Enchanted Forest Brew Cup',
                    'Embark on a magical journey with the Enchanted Forest Brew Cup. Its whimsical forest design and vibrant colors create a mesmerizing and enchanting experience that will transport you to a magical realm.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Celestial Ember ',
                    'Ignite your spirit with the Celestial Ember Mug. Its intricate celestial design and vibrant colors create a mesmerizing and otherworldly experience that will transport you to the cosmos.',
                    fake()->randomFloat(2, 10, 100),
                ),
            ]),
            Category::make('Mouse Pads', 'magenta')->products([
                Product::make(
                    Str::random(),
                    'Tech Terrain',
                    'Navigate the digital landscape with the Tech Terrain Mouse Pad. Its futuristic circuit board design and vibrant colors create a mesmerizing and immersive experience that will transport you to a cyberpunk world.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Cosmic Glow',
                    'Illuminate your workspace with the Cosmic Glow Mouse Pad. Its intricate celestial design and vibrant colors create a mesmerizing and otherworldly experience that will transport you to the cosmos.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Neon Fusion',
                    'Immerse yourself in a neon wonderland with the Neon Fusion Mouse Pad. Its vibrant neon hues and abstract patterns create a mesmerizing and electrifying experience that will transport you to a futuristic world.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Midnight Marble',
                    'Elevate your workspace with the Midnight Marble Mouse Pad. Its elegant marble design and sleek black color create a sophisticated and luxurious experience that will transform your desk into a stylish oasis.',
                    fake()->randomFloat(2, 10, 100),
                ),
            ]),
            Category::make('Stickers', 'yellow')->products([
                Product::make(
                    Str::random(),
                    'CLI Lab',
                    'Show off your coding skills with the CLI Lab Sticker. Its sleek and minimalist design is perfect for developers and tech enthusiasts who want to add a touch of style to their laptops, water bottles, and more.',
                    fake()->randomFloat(2, 10, 100),
                ),
                Product::make(
                    Str::random(),
                    'Joe Codes',
                    'Join the Joe Codes community with the Joe Codes Sticker. Its fun and playful design is perfect for developers and tech enthusiasts who want to show off their love for coding and programming.',
                    fake()->randomFloat(2, 10, 100),
                ),
            ]),
        ]);

        $this->cart = new Cart();

        $this->listener = KeyPressListener::for($this);

        $this->navigateCategories();
    }

    public function navigateCategories()
    {
        $this->state = 'categories';

        $this->listener
            ->clearExisting()
            ->listenForQuit()
            ->onRight(fn () => $this->selectedCategory = min($this->selectedCategory + 1, $this->categories->count() - 1))
            ->onLeft(fn () => $this->selectedCategory = max($this->selectedCategory - 1, 0))
            ->onUp(function () {
                $category = $this->categories->get($this->selectedCategory);
                $category->selectedProduct = max($category->selectedProduct - 1, 0);
            })
            ->onDown(function () {
                $category = $this->categories->get($this->selectedCategory);
                $category->selectedProduct = min($category->selectedProduct + 1, $category->products->count() - 1);
            })
            ->on('a', function () {
                if ($this->state === 'checkout') {
                    return;
                }

                $category = $this->categories->get($this->selectedCategory);
                $product = $category->products->get($category->selectedProduct);

                $this->cart->add($product);

                $this->state = 'added';
                $this->render();

                usleep(750_000);

                $this->state = 'categories';
                $this->render();
            })
            ->on('c', function () {
                if ($this->cart->total() === 0) {
                    return;
                }

                $this->cart->checkout();
                $this->state = 'checkout';
                $this->render();
            })
            ->listen();
    }

    public function value(): mixed
    {
        return null;
    }
}
