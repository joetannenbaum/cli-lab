<?php

namespace App\Lab\Renderers;

use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\iPod\ImportedPhotos;
use App\Lab\iPod\ImportingPhotos;
use App\Lab\iPod\ImportPhotosInfo;
use App\Lab\iPod;
use App\Lab\iPod\PlayerScreen;
use App\Lab\iPod\PlaylistScreen;
use Illuminate\Support\Collection;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class iPodRenderer extends Renderer
{
    use Aligns;
    use DrawsBoxes;
    use DrawsHotkeys;

    public function __invoke(iPod $ipod): string
    {
        $width = $ipod->terminal()->cols() - 2;
        $height = $ipod->terminal()->lines() - 6;

        if (!$ipod->authed) {
            $this->center(
                [
                    'Authorize your Spotify account:',
                    '',
                    $this->bold($this->cyan(route('spotify.auth', ['token' => $ipod->authKey]))),
                    '',
                    $this->dim('Waiting for authorization...'),
                ],
                $width,
                $height,
            )->each(fn ($line) => $this->line($line));

            return $this;
        }

        $output = $this->renderScreen($ipod);

        $this->hotkey('↑ ↓', 'Scroll');
        $this->hotkey(
            'Enter',
            $ipod->screens->get($ipod->screenIndex) instanceof PlaylistScreen || $ipod->screens->get($ipod->screenIndex) instanceof PlayerScreen
                ? 'Play'
                : 'Select',
            ($ipod->screens->get($ipod->screenIndex) instanceof PlayerScreen) === false,
        );
        $this->hotkey('←', 'Previous', $ipod->screenIndex > 0);
        $this->hotkey('q', 'Quit');

        $output = $output->concat($this->hotkeys());

        $this->center($output, $width, $height)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function renderScreen(iPod $ipod): Collection
    {
        $width = 30;

        $this->minWidth = $width;

        $currentScreen = $ipod->screens->get($ipod->screenIndex);
        $nextScreen = $ipod->screens->get($ipod->nextScreenIndex);

        $line = $this->centerHorizontally($nextScreen->title, $width)->first();

        if ($currentScreen instanceof PlayerScreen) {
            $playIcon = $currentScreen->playing ? '▶ ' : '❚❚ ';
        } else {
            $playIcon = '';
        }

        $battery = ' ' . str_repeat('▅', 3) . '▪';

        $line = $playIcon . mb_substr(mb_substr($line, 0, $width - mb_strwidth($battery)), mb_strwidth($playIcon)) . $battery;

        $lines = collect([$this->bold($line)]);

        $lines->push(str_repeat('─', $width));

        $currentScreenLines = $this->getScreenAtIndex($ipod, $ipod->screenIndex, $width);
        $currentScreenMenuStartIndex = 0;

        if ($ipod->screenIndex === $ipod->nextScreenIndex) {
            $currentScreenLines
                ->map(fn ($line) => $currentScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false ? $this->bold($line) : $line)
                ->map(fn ($line, $index) => $currentScreen->items->count() > 0 && $index - $currentScreenMenuStartIndex === $currentScreen->index ? $this->inverse($line) : $line)
                ->each(fn ($line) => $lines->push($line));

            $this->box('', $lines->implode(PHP_EOL));

            $output = $this->output;

            $this->output = '';

            return collect(explode(PHP_EOL, $output));
        }

        $nextScreenLines = $this->getScreenAtIndex($ipod, $ipod->nextScreenIndex, $width);

        $goingForward = $ipod->screenIndex < $ipod->nextScreenIndex;

        $nextScreenMenuStartIndex = 0;

        $currentBoldLines = $currentScreenLines->filter(fn ($line) => $currentScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false)->keys();
        $nextBoldLines = $nextScreenLines->filter(fn ($line) => $nextScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false)->keys();

        $currentScreenLines = $currentScreenLines->map(fn ($line) => $goingForward ? mb_substr($line, $ipod->frame) : mb_substr($line, 0, $width - $ipod->frame))
            ->map(fn ($line, $index) => $currentScreen->items->count() > 0 && $index - $currentScreenMenuStartIndex === $currentScreen->index ? $this->inverse($line) : $line)
            ->map(fn ($line, $index) => $currentBoldLines->contains($index) ? $this->bold($line) : $line);

        $nextScreenLines = $nextScreenLines->map(fn ($line) => $goingForward ? mb_substr($line, 0, $ipod->frame) : mb_substr($line, $width - $ipod->frame))
            ->map(fn ($line, $index) => $nextScreen->items->count() > 0 && $index - $nextScreenMenuStartIndex === $nextScreen->index ? $this->inverse($line) : $line)
            ->map(fn ($line, $index) => $nextBoldLines->contains($index) ? $this->bold($line) : $line);

        $screenLines = $goingForward ? $currentScreenLines->zip($nextScreenLines) : $nextScreenLines->zip($currentScreenLines);

        $screenLines->map(fn ($lines) => $lines->implode(''))
            ->each(fn ($line) => $lines->push($line));

        $ipod->frame++;

        if ($ipod->frame === $width) {
            $ipod->frame = 0;
            $ipod->screenIndex = $ipod->nextScreenIndex;
        }

        $this->box('', $lines->implode(PHP_EOL));

        $output = $this->output;

        $this->output = '';

        return collect(explode(PHP_EOL, $output));
    }

    protected function getScreenAtIndex($ipod, $screenIndex, $width): Collection
    {
        $screen = $ipod->screens->get($screenIndex);

        if ($screen instanceof PlayerScreen) {
            return $this->getPlayerScreenLines($screen, $width);
        }

        if (method_exists($screen, 'visible')) {
            $items = $screen->visible();
        } else {
            $items = $screen->items;
        }

        $lines = $items->map(
            fn ($item) => mb_str_pad(
                ' ' . $this->truncate($item, $width - 5),
                $width - 2,
                ' ',
                STR_PAD_RIGHT
            ) . ($screen->arrows ? '> ' : '  '),
        );

        if ($screen instanceof ImportPhotosInfo) {
            $infoLines = $this->getImportPhotosInfoLines($screen, $width);

            $infoLines->push(str_repeat('─', $width));

            $lines = $infoLines->merge($lines);
        }

        if ($screen instanceof ImportingPhotos) {
            $infoLines = $this->getImportingPhotosLines($screen, $width);

            $infoLines->push(str_repeat('─', $width));

            $lines = $infoLines->merge($lines);
        }

        if ($screen instanceof ImportedPhotos) {
            $infoLines = $this->getCompletedImportLines($screen, $width);

            $infoLines->push(str_repeat('─', $width));

            $lines = $infoLines->merge($lines);
        }

        while ($lines->count() < 8) {
            $lines->push(str_repeat(' ', $width));
        }

        return $lines;
    }

    protected function getPlayerScreenLines(PlayerScreen $player, $width)
    {
        $track = $player->track['track'];

        $totalSeconds = floor($track['duration_ms'] / 1000);
        $minutes = floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        $progressSeconds = time() - $player->startedAt;

        $progressMinutes = floor($progressSeconds / 60);
        $progressSeconds = $progressSeconds % 60;

        $progressFormatted = str_pad($progressMinutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($progressSeconds, 2, '0', STR_PAD_LEFT);
        $durationFormatted = str_pad($minutes, 2, '0', STR_PAD_LEFT) . ':' . str_pad($seconds, 2, '0', STR_PAD_LEFT);

        $times = $progressFormatted . str_repeat(' ', $width - (mb_strwidth($progressFormatted) + mb_strwidth($durationFormatted))) . $durationFormatted;

        $barWidth = $width;
        $filledPercent = floor(($progressSeconds / $totalSeconds) * $barWidth);
        $emptyPercent = $barWidth - $filledPercent;
        $bar = str_repeat('▓', $filledPercent) . str_repeat('░', $emptyPercent);

        $lines = $this->centerHorizontally(collect([
            '',
            $this->bold($this->truncate($track['name'], $width - 4)),
            $this->bold($this->truncate(collect($track['artists'])->pluck('name')->implode(', '), $width - 4)),
            $this->bold($this->truncate($track['album']['name'], $width - 4)),
            '',
            $bar,
            $times,
        ]), $width);

        $lines->prepend("{$track['track_number']} of {$track['album']['total_tracks']}");

        while ($lines->count() < 8) {
            $lines->push(str_repeat(' ', $width));
        }

        return $lines;
    }

    protected function getImportPhotosInfoLines(ImportPhotosInfo $screen, $width): Collection
    {
        return collect([
            '',
            '  Type: Media card',
            'Photos: 6',
            '  Free: 62.6 MB of 62.9 M',
            '',
        ])->map(fn ($line) => str_repeat(' ', 4) . $line)->map(fn ($line) => str_pad($line, $width - 2, ' ', STR_PAD_RIGHT));
    }

    protected function getCompletedImportLines(ImportedPhotos $screen, $width): Collection
    {
        return collect([
            '',
            '    Type: Media card',
            'Imported: 6 of 6',
            '    Free: 62.6 MB of 62.9 M',
            '',
        ])->map(fn ($line) => str_repeat(' ', 2) . $line);
    }

    protected function getImportingPhotosLines(ImportingPhotos $screen, $width): Collection
    {
        $barWidth = $width - 6;
        $filledPercent = floor(($screen->imported / $screen->total) * $barWidth);
        $emptyPercent = $barWidth - $filledPercent;
        $bar = str_repeat('▓', $filledPercent) . str_repeat('░', $emptyPercent);
        $lines = collect([
            '',
            $bar,
            // "{$screen->imported} of {$screen->total}",
            $this->bold("{$screen->imported} of {$screen->total}"),
            // $screen->imported % 2 === 0 ? '' : 'Importing',
            $screen->imported % 2 === 0 ? '' : $this->bold('Importing'),
            '',
        ]);

        return $this->centerHorizontally($lines, $width);
    }
}
