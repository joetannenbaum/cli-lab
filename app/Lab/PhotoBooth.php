<?php

namespace App\Lab;

use App\Lab\Renderers\PhotoBoothRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process as FacadesProcess;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use SplFileInfo;

class PhotoBooth extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;
    use SetsUpAndResets;

    public array $artLines = [];

    protected array $characters;

    protected string $dir;

    protected SplFileInfo $currentImage;

    protected $savedPhotos = [];

    protected string $saveDir;

    protected string $recordingDir;

    protected string $asciiDir;

    public bool $recentlyCaptured = false;

    protected int $captureCountdown = 0;

    protected int $brightness = 5;

    public int $waitingTicks = 1;

    protected int $waitingDirection = 1;

    public int $height;

    public int $width;

    public int $boothHeight;

    public bool $recording = false;

    protected array $currentRecording = [];

    protected string $recordingId;

    protected Process $childProcess;

    protected string $currentDevice = 'computer';

    protected string $imageIdentifier;

    protected Collection $fromPhone;

    protected string $phoneDir = '/Users/joetannenbaum/Dropbox/Rando';

    public array $latestFromPhone = [];

    public int $latestFromPhoneCountdown = 0;

    public function __construct()
    {
        $this->registerRenderer(PhotoBoothRenderer::class);

        $this->setBrightness();

        $this->createAltScreen();

        $dir = Str::slug(now()->toDateTimeString());

        // $dir = '2024-08-10-132028';

        $this->dir = storage_path('video/' . $dir);
        $this->saveDir = storage_path('video/saved/' . $dir);
        $this->recordingDir = storage_path('video/recording/' . $dir);
        $this->asciiDir = $this->saveDir . '/ascii';

        File::ensureDirectoryExists($this->dir);
        File::ensureDirectoryExists($this->saveDir);
        File::ensureDirectoryExists($this->recordingDir);
        File::ensureDirectoryExists($this->asciiDir);

        $this->height = $this->terminal()->lines() - 4;
        $this->width = $this->terminal()->cols() - 4;

        $this->boothHeight = floor($this->height * .75);

        $this->fromPhone = collect(File::files($this->phoneDir))->map(fn($file) => $file->getFilename());

        $this->setup($this->startBooth(...));
    }

    protected function setBrightness()
    {
        $this->characters = str_split(
            str_repeat(' ', $this->brightness * 10)
                . '`.-\':_,^=;><+!rc*/z?sLTv)J7(|Fi{C}fI31tlu[neoZ5Yxjya]2ESwqkP6h9d4VpOGbUAKXHm8RD#$Bg0MNWQ%&@'
        );
    }

    protected function startBooth()
    {
        $this->childProcess = $this->getProcess('computer');

        $listener = KeyPressListener::for($this)
            ->on([Key::CTRL_C, 'q'], function () {
                $this->childProcess->terminate();
                $this->terminal()->exit();
            })
            ->on(Key::SPACE, fn() => $this->savePhoto())
            ->onUp(function () {
                $this->brightness = max($this->brightness - 1, 0);
                $this->setBrightness();
            })
            ->onDown(function () {
                $this->brightness = $this->brightness + 1;
                $this->setBrightness();
            })
            ->on('o', function () {
                exec("open {$this->asciiDir}");
            })
            ->on('r', function () {
                $this->recording = ! $this->recording;

                if ($this->recording) {
                    $this->recordingId = Str::slug(now()->toDateTimeString('microsecond'));
                    File::ensureDirectoryExists("{$this->recordingDir}/{$this->recordingId}");
                    $this->currentRecording = [];
                } else if (count($this->currentRecording)) {
                    $gifName = Str::slug(now()->toDateTimeString()) . '.gif';
                    $gifProcess = new Process("magick -delay 10 -loop 0 {$this->recordingDir}/{$this->recordingId}/*.jpg {$this->asciiDir}/{$gifName}");

                    $gifProcess->start();
                }
            });

        $loop = Loop::get();

        $loop->addPeriodicTimer(0.1, function () use ($listener) {
            $listener->once();

            $latestFromPhone = collect(File::files($this->phoneDir))
                ->filter(fn($file) => ! $this->fromPhone->contains($file->getFilename()))
                ->first();

            if ($latestFromPhone) {
                $this->fromPhone->push($latestFromPhone->getFilename());
                $this->analyzeImage($latestFromPhone->getPathname(), true);
                $this->savePhonePhoto();
            }

            $latestImage = collect(File::files($this->dir))
                ->sortBy(fn($file) => $file->getFilename(), SORT_NATURAL)
                ->last();

            if ($this->latestFromPhoneCountdown > 0) {
                $this->latestFromPhoneCountdown--;
            }

            if ($this->captureCountdown > 0) {
                $this->captureCountdown--;
            } else {
                $this->recentlyCaptured = false;
            }

            if ($latestImage) {
                $this->currentImage = $latestImage;
                $this->analyzeImage($latestImage->getPathname());

                if ($this->recording) {
                    $this->savePhoto();
                }
            } else {
                $this->render();

                if ($this->waitingTicks === 22 || $this->waitingTicks === 0) {
                    $this->waitingDirection *= -1;
                }

                $this->waitingTicks += $this->waitingDirection;
            }
        });

        $this->childProcess->start();

        $loop->run();
    }

    protected function getProcess(string $device)
    {
        $this->imageIdentifier = Str::slug(now()->toDateTimeString('microsecond'));
        $this->currentDevice = $device;

        $path = "{$this->dir}/{$this->imageIdentifier}-{$this->currentDevice}%d.jpg";

        $deviceIndex = $this->getDeviceIndex($device);

        $devices = [
            'computer' => '1760x1328',
            'phone' => '1920x1080',
        ];

        return new Process("ffmpeg -f avfoundation -framerate 30 -video_size {$devices[$device]} -i '{$deviceIndex}:none' -vf fps=15 {$path}");
    }

    protected function getDeviceIndex(string $device)
    {
        $result = FacadesProcess::run('ffmpeg -f avfoundation -list_devices true -i ""');

        $lines = collect(explode(PHP_EOL, $result->errorOutput()))->filter(function ($line) {
            return str_contains($line, 'Camo Camera') || str_contains($line, 'FaceTime HD Camera');
        })->values();

        return $lines->search(function ($line) use ($device) {
            if ($device === 'computer') {
                return str_contains($line, 'FaceTime HD Camera');
            }

            return str_contains($line, 'Camo Camera');
        });
    }

    protected function toggleDevice()
    {
        return $this->getProcess($this->currentDevice === 'computer' ? 'phone' : 'computer');
    }

    protected function savePhoto()
    {
        if (!$this->recording) {
            $this->captureCountdown = 10;
            $this->recentlyCaptured = true;
        }

        $photo = $this->currentImage->getFilename();
        $newPhoto = $this->saveDir . '/' . $photo;

        File::copy($this->currentImage->getPathname(), $newPhoto);
        File::put($this->saveDir . '/' . $this->currentImage->getBasename('.jpg') . '.txt', implode(PHP_EOL, $this->artLines));

        $this->savedPhotos[] = $this->artLines;

        $fontSize = 12;
        $lineHeight = $fontSize * 1.5;

        $width = strlen($this->artLines[0]) * 10;
        $height = ($this->boothHeight * $lineHeight) + 5;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $black);

        $font = storage_path('fonts/FiraCode-Regular.ttf');

        $y = 0;

        $imageLines = array_slice($this->artLines, 0, $this->boothHeight);

        foreach ($imageLines as $line) {
            imagettftext($image, $fontSize, 0, 0, $y += $lineHeight, $white, $font, $line);
        }

        $photoPath = $this->recording ? "{$this->recordingDir}/{$this->recordingId}" : $this->asciiDir;

        $asciiName = Str::slug(now()->toDateTimeString('microsecond')) . '.jpg';

        $asciiFilename = "{$photoPath}/{$asciiName}";

        imagejpeg($image, $asciiFilename, 100);

        if ($this->recording) {
            $this->currentRecording[] = $asciiFilename;
        }
    }

    protected function savePhonePhoto()
    {
        $fontSize = 12;
        $lineHeight = $fontSize * 1.5;

        $width = strlen($this->artLines[0]) * 10;
        $height = ($this->boothHeight * $lineHeight) + 5;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $black);

        $font = storage_path('fonts/FiraCode-Regular.ttf');

        $y = 0;

        $imageLines = array_slice($this->latestFromPhone, 0, $this->boothHeight);

        foreach ($imageLines as $line) {
            imagettftext($image, $fontSize, 0, 0, $y += $lineHeight, $white, $font, $line);
        }

        $photoPath = $this->recording ? "{$this->recordingDir}/{$this->recordingId}" : $this->asciiDir;

        $asciiName = Str::slug(now()->toDateTimeString('microsecond')) . '.jpg';

        $asciiFilename = "{$photoPath}/{$asciiName}";

        imagejpeg($image, $asciiFilename, 100);
    }

    protected function analyzeImage(string $imagePath, bool $inBackground = false)
    {
        $image = imagecreatefromjpeg($imagePath);

        $newWidth = 150;
        $newHeight = imagesy($image) * ($newWidth / imagesx($image));
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, imagesx($image), imagesy($image));
        $image = $newImage;

        // Image width
        $width = imagesx($image);
        $height = imagesy($image);

        $lines = [];

        for ($y = 0; $y < $height; $y++) {
            $line = '';
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $average = ($r + $g + $b) / 3;
                $percentage = $average / 255;
                $character = $this->characters[(int) max(($percentage * count($this->characters)) - 1, 0)];
                $line .= $character;
            }
            $lines[] = $line;
        }

        if ($inBackground) {
            $this->latestFromPhone = $lines;
            $this->latestFromPhoneCountdown = 50;
        } else {
            $this->artLines = $lines;
        }

        $this->render();
    }

    public function value(): mixed
    {
        return null;
    }
}

// 1080x1920
// 1280x720
// 1328x1760
// 1552x1552
// 1760x1328
// 1920x1080
// 640x480
