<?php

namespace App\Lab\Renderers;

use App\Lab\DirectoryWatcher;
use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Illuminate\Support\Collection;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class DirectoryWatcherRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsHotkeys;
    use Aligns;

    public function __invoke(DirectoryWatcher $prompt): string
    {
        $height = $prompt->terminal()->lines() - 6;

        $rows = $prompt->items->map(fn ($item) => $this->getRow($item, $prompt));

        $fileLines = $this->getTable($rows);

        $fileLines->prepend('');
        $fileLines->prepend($this->dim(' Total: ') . $prompt->total);

        $this->padVertically($fileLines, $height);

        $fileLines->each($this->line(...));

        return $this;
    }

    protected function getRow($item, DirectoryWatcher $prompt): array
    {
        $pastVersions = $prompt->versions
            ->map(fn ($version) => $version->firstWhere('name', $item['name']))
            ->filter();

        $row = [
            'permissions' => $this->dim($item['permissions']),
            'owner' => $item['owner'],
            'group' => $item['group'],
            'size' => $this->dim($item['size']),
            'date' => $this->dim($item['date']),
            'name' => $item['is_dir'] ? $this->cyan($item['name']) : $item['name'],
        ];

        $itemIsNew = $prompt->versions->count() > 0 && $pastVersions->count() < $prompt->versions->count() / 2;

        return collect($row)
            ->map(function ($value, $key) use ($pastVersions, $item, $itemIsNew) {
                if ($itemIsNew) {
                    return $this->green($item[$key]);
                }

                $changed = $pastVersions->filter(fn ($version) => $version[$key] !== $item[$key]);

                return ($changed->isNotEmpty()) ? $this->yellow($item[$key]) : $value;
            })
            ->values()
            ->all();
    }

    protected function getTable(Collection $rows): Collection
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

        $output = trim($buffered->content(), PHP_EOL);

        return collect(explode(PHP_EOL, $output));
    }
}
