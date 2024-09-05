<?php

namespace App\Lab;

use App\Lab\GifViewer\Gif;
use App\Lab\Renderers\GifViewerRenderer;
use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process as FacadesProcess;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Illuminate\Support\Str;
use React\ChildProcess\Process;
use React\EventLoop\Loop;
use SplFileInfo;

class GifViewer extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersRenderers;
    use TypedValue;
    use SetsUpAndResets;
    use Loops;

    public array $artLines = [];

    protected array $characters;

    protected array $editingCharacters;

    protected ?string $dir = null;

    protected SplFileInfo $currentImage;

    protected $savedPhotos = [];

    protected string $saveDir;

    protected string $recordingDir;

    protected string $asciiDir;

    public bool $recentlyCaptured = false;

    protected int $captureCountdown = 0;

    protected int $brightness = 5;

    protected int $editingBrightness = 5;

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

    public int $editingOffset = 0;

    public int $previewingOffset = 0;

    protected $baseCharacters = '`.-\':_,^=;><+!rc*/z?sLTv)J7(|Fi{C}fI31tlu[neoZ5Yxjya]2ESwqkP6h9d4VpOGbUAKXHm8RD#$Bg0MNWQ%&@';

    public ?Gif $gif = null;

    public $query = '';

    public $gifIndex = 0;

    public $response;

    public function __construct()
    {
        $this->registerRenderer(GifViewerRenderer::class);

        $this->setBrightness();

        $this->state = 'searching';

        $this->createAltScreen();

        $this->height = $this->terminal()->lines() - 4;
        $this->width = $this->terminal()->cols() - 4;

        $this->setup($this->startBooth(...));
    }

    protected function setBrightness()
    {
        $this->characters = str_split(
            str_repeat(' ', $this->brightness * 10) . $this->baseCharacters
        );
    }

    protected function setEditingBrightness()
    {
        $this->editingCharacters = str_split(
            str_repeat(' ', $this->editingBrightness * 10) . $this->baseCharacters
        );
    }

    protected function startBooth()
    {
        $listener = KeyPressListener::for($this)
            ->on([Key::CTRL_C], function () {
                $this->terminal()->exit();
            })
            ->on('/', function () {
                $this->state = 'searching';
            })
            ->wildcard(function ($key) {
                if ($this->state === 'searching') {
                    $this->query .= $key;
                }
            })
            ->on(Key::BACKSPACE, function () {
                $this->query = substr($this->query, 0, -1);
            })
            ->on(Key::ENTER, function () {
                if ($this->state === 'searching') {
                    $this->state = 'viewing';
                    $this->gif = null;

                    $this->render();

                    $this->gifIndex = 0;
                    $this->search($this->query);
                    $this->setGif();
                    $this->query = '';
                } else {
                    $this->gifIndex++;
                    $this->setGif();
                }
            })
            ->onRight(function () {
                $this->gif->adjustBrightness(-1);
            })
            ->onLeft(function () {
                $this->gif->adjustBrightness(1);
            });

        $this->loop(function () use ($listener) {
            $listener->once();
            $this->render();
        });
    }

    protected function setGif()
    {


        $first = $this->response['data'][$this->gifIndex];

        // $first = ['id' => 'josh'];

        $destination = storage_path('gif-viewer/originals/' . $first['id'] . '.mp4');

        if (!File::exists($destination)) {
            File::ensureDirectoryExists(storage_path('gif-viewer/originals'));

            file_put_contents(
                $destination,
                file_get_contents(sprintf('https://i.giphy.com/%s.mp4', $first['id'])),
            );
        }

        $this->dir = storage_path('gif-viewer/frames/' . $first['id']);

        if (!File::exists($this->dir)) {
            File::ensureDirectoryExists($this->dir);

            $command = "ffmpeg -i {$destination} -vf fps=10 " . $this->dir . '/%d.jpg 2> /dev/null';

            // echo $command . PHP_EOL;
            exec($command);
        }

        $this->gif = new Gif($this->dir, $this->width, $this->height);

        $this->clearRegisteredLoopables();
        $this->registerLoopable($this->gif);
    }

    protected function search(string $query)
    {
        $path = storage_path("giphy-{$query}.json");

        if (!File::exists($path)) {
            $this->response = Http::withOptions(['sink' => $path])->get('https://api.giphy.com/v1/gifs/search', [
                'q' => $query,
                'api_key' => '0TuBniGYGNXcuiqVPI5qGcohfnrFuCxQ',
            ]);
        } else {
            $this->response = json_decode(File::get($path), true);
        }
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

        $framerate = $device === 'computer' ? 30 : 60;

        info("ffmpeg -f avfoundation -framerate {$framerate} -video_size {$devices[$device]} -i '{$deviceIndex}:none' -vf fps=15 {$path}");

        return new Process("ffmpeg -f avfoundation -framerate {$framerate} -video_size {$devices[$device]} -i '{$deviceIndex}:none' -vf fps=15 {$path}");
    }

    protected function getDeviceIndex(string $device)
    {
        $result = FacadesProcess::run('ffmpeg -f avfoundation -list_devices true -i ""');

        $line = collect(explode(PHP_EOL, $result->errorOutput()))->first(function ($line) use ($device) {
            if ($device === 'computer') {
                return str_contains($line, 'FaceTime HD Camera');
            }

            return str_contains($line, 'Android Webcam');
        });

        if ($line) {
            info(preg_match('/\[\d\]/', $line, $matches) ? trim($matches[0], '[]') : null);
            return preg_match('/\[\d\]/', $line, $matches) ? trim($matches[0], '[]') : null;
        }

        return null;
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
        $lineHeight = $fontSize * 2;

        $width = strlen($this->artLines[0]) * 10;
        $height = ($this->boothHeight * $lineHeight) + 5;

        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $black);

        $font = storage_path('fonts/FiraCode-Regular.ttf');

        $y = 0;

        $startAt = (int) floor((count($this->artLines) - $this->boothHeight) / 2);

        $imageLines = array_slice($this->artLines, $startAt, $this->boothHeight);

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

        $imageLines = array_slice($this->latestFromPhone, $this->editingOffset, $this->boothHeight);

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

                $characters = $inBackground ? $this->editingCharacters : $this->characters;

                $character = $characters[(int) max(($percentage * count($characters)) - 1, 0)];

                $line .= $character;
            }

            $lines[] = $line;
        }

        if ($inBackground) {
            $this->latestFromPhone = $lines;
        } else {
            $this->artLines = $lines;
        }
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
