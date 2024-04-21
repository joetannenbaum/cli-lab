<?php

namespace App\Lab;

use App\Lab\Renderers\StickerRenderer;
use App\Lab\Sticker\Bar;
use App\Lab\Sticker\Input;
use App\Models\Sticker as StickerModel;
use Chewie\Art;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Sticker extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use Loops;

    public Collection $inputs;

    protected KeyPressListener $listener;

    public int $focused = 0;

    public int $stickersLeft;

    protected int $totalStickers = 80;

    public int $barCount = 0;

    public int $barHeight = 0;

    public Collection $bars;

    public function __construct()
    {
        $this->registerRenderer(StickerRenderer::class);

        Art::setDirectory(storage_path('ascii/alphabet'));

        $this->createAltScreen();

        $this->stickersLeft = $this->totalStickers - StickerModel::count();

        $this->listener = KeyPressListener::for($this);

        $this->listener
            ->listenForQuit()
            ->on(Key::ENTER, $this->form(...))
            ->listen();

        $this->barCount = $this->terminal()->cols() - 2;

        $this->barHeight = $this->terminal()->lines() - 4;

        $this->bars = collect();

        $this->bars = collect(range(1, $this->barCount))->map(fn () => new Bar($this->barHeight));

        $this->registerLoopables(...$this->bars);

        $defaultListeners = [
            [Key::TAB, $this->onTab(...)],
            [Key::SHIFT_TAB, $this->onShiftTab(...)],
            [Key::ENTER, $this->onSubmit(...)],
            [Key::CTRL_C, fn () => $this->terminal()->exit()],
        ];

        $this->inputs = collect([
            new Input('Name', 'name', ['required']),
            new Input('Twitter Handle (optional)', 'twitter'),
            new Input('Address 1', 'address1', ['required']),
            new Input('Address 2', 'address2', ['required']),
            new Input('Address 3', 'address3'),
            new Input('Address 4', 'address4'),
            // new Input('City', 'city', ['required']),
            // new Input('State', 'state', ['required']),
            // new Input('Zip', 'zip', ['required']),
            new Input('Verification URL', 'verification_url', ['required', 'url'], 'Sensibly redacted screenshot of open source support'),
            new Input('Note (optional)', 'note', ['max:255']),
        ])->each(fn (Input $input) => $input->listener($this->listener, $defaultListeners));
    }

    protected function form()
    {
        $this->state = 'form';

        $this->inputs->first()->focus();
    }

    protected function onTab()
    {
        $this->inputs->get($this->focused)->unfocus();

        $this->focused++;

        if ($this->focused >= $this->inputs->count()) {
            $this->focused = 0;
        }

        $this->inputs->get($this->focused)->focus();
    }

    protected function onShiftTab()
    {
        $this->inputs->get($this->focused)->unfocus();

        $this->focused--;

        if ($this->focused < 0) {
            $this->focused = $this->inputs->count() - 1;
        }

        $this->inputs->get($this->focused)->focus();
    }

    protected function onSubmit()
    {
        $this->inputs->each(fn (Input $input) => $input->validate());

        if ($this->inputs->every(fn (Input $input) => $input->isValid)) {
            $this->listener
                ->clearExisting()
                ->listenForQuit()
                ->on(Key::ENTER, $this->confirmed(...))
                ->on('e', function () {
                    $this->inputs->get($this->focused)->unfocus();
                    $this->inputs->get(0)->focus();
                    $this->focused = 0;
                    $this->form();
                })
                ->listen();

            $this->state = 'confirm';
            $this->render();
        }
    }

    protected function confirmed()
    {
        $params = $this->inputs->mapWithKeys(fn (Input $input) => [$input->key => $input->value()])
            ->map(fn ($value) => $value === '' ? null : $value)
            ->merge([
                'city' => '',
                'state' => '',
                'zip' => '',
            ]);

        StickerModel::create($params->toArray());

        $this->state = 'submitted';

        $this->listener->clearExisting()->on(['q', Key::CTRL_C], function () {
            $this->exitAltScreen();
            $this->terminal()->exit();
        });

        $this->loop(function () {
            $this->render();
            $this->listener->once();
        });
    }

    public function value(): mixed
    {
        return null;
    }
}
