<?php

namespace App\Lab\Renderers;

use App\Lab\DirectoryWatcher;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Output\Lines;
use Illuminate\Support\Collection;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class DirectoryWatcherFullRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use Aligns;

    protected int $width;

    protected int $height;

    protected int $activityLogWidth;

    public function __invoke(DirectoryWatcher $prompt): string
    {
        $this->width = $prompt->terminal()->cols() - 2;
        $this->height = $prompt->terminal()->lines() - 6;

        $fileListWidth = $prompt->showActivityLog ? (int) floor($this->width * .7) : $this->width;
        $this->activityLogWidth = $this->width - $fileListWidth - 2;

        $rows = $prompt->items->map(fn ($item) => $this->getFileLine($item, $prompt));

        $fileLines = $this->getFileTable($rows);
        $activityLogLines = $this->renderActivityLog($prompt);

        $fileLines->prepend('');
        $fileLines->prepend($this->dim(' Total: ') . $prompt->total);

        $this->padVertically($fileLines, $this->height);

        $fileLines->each($this->line(...));

        // Lines::fromColumns([
        //     $fileLines,
        //     $activityLogLines,
        // ])->spacing(2)->lines()->each(fn ($line) => $this->line($line));

        // $this->pinToBottom($this->height, function () use ($prompt) {
        //     $this->newLine();

        //     $this->hotkey('a', $prompt->showActivityLog ? 'Hide activity log' : 'Show activity log');

        //     collect($this->hotkeys())->each(fn ($hotkey) => $this->line('  ' . $hotkey));
        // });

        return $this;
    }

    protected function getFileLine($item, DirectoryWatcher $prompt): array
    {
        $pastVersions = $prompt->versions
            ->map(fn ($version) => $version->firstWhere('name', $item['name']))
            ->filter();

        if ($prompt->versions->count() > 0 && $pastVersions->count() < $prompt->versions->count() / 2) {
            // This file just showed up
            return [
                $this->green($item['permissions']),
                $this->green($item['owner']),
                $this->green($item['group']),
                $this->green($item['size']),
                $this->green($item['date']),
                $this->green($item['name']),
            ];
        }

        $row = [
            'permissions' => $this->dim($item['permissions']),
            'owner' => $item['owner'],
            'group' => $item['group'],
            'size' => $this->dim($item['size']),
            'date' => $this->dim($item['date']),
            'name' => $item['is_dir'] ? $this->cyan($item['name']) : $item['name'],
        ];

        return collect($row)
            ->map(function ($value, $key) use ($pastVersions, $item) {
                $changed = $pastVersions->filter(fn ($version) => $version[$key] !== $item[$key]);

                return ($changed->isNotEmpty()) ? $this->yellow($item[$key]) : $value;
            })
            ->values()
            ->all();
    }

    protected function getFileTable(Collection $rows): Collection
    {
        $buffered = new BufferedConsoleOutput();

        $tableStyle = (new TableStyle())
            ->setHorizontalBorderChars('')
            ->setVerticalBorderChars('', '')
            ->setCellHeaderFormat($this->dim('<fg=default>%s</>'))
            ->setCellRowFormat('<fg=default>%s</>');

        $tableStyle->setCrossingChars('', '', '', '', '', '</>', '', '', '', '<fg=gray>', '', '');

        (new Table($buffered))
            ->setRows($rows->toArray())
            ->setStyle($tableStyle)
            ->render();

        return collect(explode(PHP_EOL, trim($buffered->content(), PHP_EOL)));
    }


    protected function renderActivityLog(DirectoryWatcher $prompt): Collection
    {
        if (!$prompt->showActivityLog) {
            return collect();
        }

        $activityLog = $prompt->log->map(function ($log) {
            return [
                $this->dim(date('H:i:s', $log['timestamp'])),
                match ($log['type']) {
                    'created'  => $this->green('+') . '  ' . $log['item']['name'],
                    'deleted'  => $this->red('-') . '  ' . $log['item']['name'],
                    'modified' => [
                        $this->yellow('▵') . '  ' . $log['item']['name'],
                        str_repeat(' ', 3) . $log['from'] . $this->dim(' → ') . $log['to'],
                    ],
                },
                '',
            ];
        })->flatten();

        $visibleActivityLog = $activityLog->slice(-$this->height);

        $activityLogLines = $this->scrollbar(
            $visibleActivityLog,
            $visibleActivityLog->keys()->first() ?? 0,
            $this->height,
            $activityLog->count(),
            $this->activityLogWidth
        );

        $this->box('Activity Log', $activityLogLines->implode(PHP_EOL));

        $activityLogLines = collect(explode(PHP_EOL, trim($this->output, PHP_EOL)));

        $this->output = '';

        return $activityLogLines;
    }
}
