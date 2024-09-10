<?php

namespace App\Lab;

use App\Lab\Renderers\PLACEHOLDERRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class PLACEHOLDER extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;

    public function __construct()
    {
        $this->registerRenderer(PLACEHOLDERRenderer::class);

        // $this->createAltScreen();
    }

    public function value(): mixed
    {
        return null;
    }
}
