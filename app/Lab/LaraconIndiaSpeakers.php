<?php

declare(strict_types=1);

namespace App\Lab;

use App\Lab\Concerns\HasSpeakers;
use App\Lab\Renderers\LaraconIndiaSpeakersRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class LaraconIndiaSpeakers extends Prompt
{
    use RegistersRenderers;
    use CreatesAnAltScreen;
    use HasSpeakers;

    public Collection $speakers;

    public int $currentSpeaker = 0;

    public int $selectedSpeaker = 0;

    public string $activeArea = 'list';

    public int $detailScrollPosition = 0;

    public function __construct()
    {
        $this->registerRenderer(LaraconIndiaSpeakersRenderer::class);

        $this->speakers = $this->loadSpeakers(true);

        $this->createAltScreen();

        KeyPressListener::for($this)
            ->onDown($this->onDown(...))
            ->onUp($this->onUp(...))
            ->onLeft(fn() => $this->activeArea = 'list')
            ->onRight(fn() => $this->activeArea = 'detail')
            ->on(Key::ENTER, $this->onEnter(...))
            ->listen();
    }

    protected function onDown()
    {
        if ($this->activeArea === 'list') {
            $this->currentSpeaker = min($this->currentSpeaker + 1, count($this->speakers) - 1);
        } else {
            $this->detailScrollPosition++;
        }
    }

    protected function onUp()
    {
        if ($this->activeArea === 'list') {
            $this->currentSpeaker = max($this->currentSpeaker - 1, 0);
        } else {
            $this->detailScrollPosition = max($this->detailScrollPosition - 1, 0);
        }
    }

    protected function onEnter()
    {
        if ($this->activeArea === 'list') {
            $this->selectedSpeaker = $this->currentSpeaker;
            $this->activeArea = 'detail';
            $this->detailScrollPosition = 0;
        }
    }

    public function value(): mixed
    {
        return null;
    }
}
