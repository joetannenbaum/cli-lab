<?php

namespace App\Lab;

use App\Events\PlayPause;
use App\Events\Seek;
use App\Events\Sync;
use App\Events\VolumeChanged;
use App\Lab\LaraconUsTalk\Gif;
use App\Lab\Renderers\LaraconUsTalkRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process as FacadesProcess;
use Illuminate\Support\LazyCollection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use SplFileInfo;

class LaraconUsTalk extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;
    use SetsUpAndResets;
    use Loops;

    public int $height;

    public int $width;

    protected $characters;

    protected $baseCharacters =  '`.-\':_,^=;><+!rc*/z?sLTv)J7(|Fi{C}fI31tlu[neoZ5Yxjya]2ESwqkP6h9d4VpOGbUAKXHm8RD#$Bg0MNWQ%&@';

    protected $contrast = 0;

    public $playing = false;

    public LazyCollection $frames;

    public $frameIndex = 0;

    public $channelId;

    public function __construct()
    {
        $this->registerRenderer(LaraconUsTalkRenderer::class);

        $this->setBrightness();

        $this->state = 'searching';

        $this->channelId = strtoupper(Str::random(10));

        // $this->createAltScreen();

        $this->height = $this->terminal()->lines() - 4;
        $this->width = $this->terminal()->cols() - 4;

        $this->frames = File::lines(storage_path('laracon-us-talk-ascii.txt'));

        $this->setup($this->start(...));
    }

    protected function setBrightness()
    {
        $this->characters = str_split(
            str_repeat(' ', $this->contrast * 10) . $this->baseCharacters
        );
    }

    protected function adjustBrightness($by)
    {
        $this->contrast = max(0, min(10, $this->contrast + $by));
        $this->setBrightness();
    }

    protected function start()
    {
        $frameRate = 15;

        $listener = KeyPressListener::for($this)
            ->on([Key::CTRL_C], function () {
                event(new PlayPause(false, $this->channelId));
                $this->terminal()->exit();
            })
            ->on(Key::SPACE, function () {
                $this->playing = !$this->playing;
                event(new PlayPause($this->playing, $this->channelId));
            })
            ->onUp(function () {
                event(new VolumeChanged(1, $this->channelId));
            })
            ->onDown(function () {
                event(new VolumeChanged(-1, $this->channelId));
            })
            ->onRight(function () use ($frameRate) {
                $this->frameIndex = $this->frameIndex + ($frameRate * 10);
                event(new Seek(floor($this->frameIndex / $frameRate), $this->channelId));
            })
            ->onLeft(function () use ($frameRate) {
                $this->frameIndex = max(0, $this->frameIndex - ($frameRate * 10));
                event(new Seek(floor($this->frameIndex / $frameRate), $this->channelId));
            });

        $this->loop(function () use ($listener, $frameRate) {
            $listener->once();
            $this->render();

            if ($this->playing) {
                $this->frameIndex++;

                if ($this->frameIndex % $frameRate === 0) {
                    event(new Sync(floor($this->frameIndex / $frameRate), $this->channelId));
                }
            }
        }, 50_000);
    }

    public function currentFrame()
    {
        return json_decode($this->frames->get($this->frameIndex));
    }

    public function value(): mixed
    {
        return null;
    }
}
