<?php

namespace App\Lab\Renderers;

use App\Lab\Blog;
use App\Lab\Concerns\Aligns;
use App\Lab\Concerns\DrawsAscii;
use App\Lab\Concerns\DrawsHotkeys;
use App\Lab\Concerns\DrawsTables;
use App\Lab\Concerns\HasMinimumDimensions;
use App\Lab\Output\Util;
use Illuminate\Support\Str;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;
use League\HTMLToMarkdown\HtmlConverter;

class BlogRenderer extends Renderer
{
    use Aligns;
    use DrawsAscii;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;
    use DrawsTables;
    use HasMinimumDimensions;

    protected int $scrollWidth;

    protected int $maxLineLength;

    protected array $spinnerFrames = ['⠂', '⠒', '⠐', '⠰', '⠠', '⠤', '⠄', '⠆'];

    public function __invoke(Blog $prompt): string
    {
        return $this->minDimensions(fn () => $this->renderState($prompt), 100, 20);
    }

    protected function renderState(Blog $prompt): self
    {
        $this->scrollWidth = min($prompt->terminal()->cols() - 2, 100);
        $this->maxLineLength = $this->scrollWidth - 6;

        if ($prompt->state === 'browse') {
            return $this->renderBrowseState($prompt);
        }

        if ($prompt->fetching) {
            $frame = $this->spinnerFrames[$prompt->spinnerCount % count($this->spinnerFrames)];

            $this->line($this->magenta($frame) . ' Fetching post...');

            return $this;
        }

        $finalLines = [];

        foreach ($prompt->post['content'] as $index => $block) {
            $lines = match ($block['type']) {
                'tweet'         => $this->drawTweet($block),
                'code_block'    => $this->drawCodeBlock($block),
                'torchlight'    => $this->drawTorchlight($block),
                'video'         => $this->drawVideo($block),
                'image'         => $this->drawImage($block),
                'text'          => $this->drawText($block),
                'callout'       => $this->drawCallout($block),
                'buttons'       => $this->drawButtons($block),
                'youtube_video' => $this->drawYouTubeVideo($block),
                default         => throw new \Exception('Unknown block type: ' . $block['type']),
            };

            $finalLines = array_merge($finalLines, $lines, ['']);
        }

        $finalLines = collect($finalLines)->implode(PHP_EOL);
        $finalLines = Util::wordwrap($finalLines, $this->maxLineLength, PHP_EOL, true);
        $finalLines = explode(PHP_EOL, $finalLines);

        $height = $prompt->terminal()->lines() - 12;

        $firstVisible = min($prompt->scrollPosition, count($finalLines) - $height);

        if ($prompt->scrollPosition > count($finalLines) - $height) {
            $prompt->scrollPosition = count($finalLines) - $height;
        }

        $this->line($this->dim($prompt->post['date']));
        $this->line($this->cyan($this->bold($prompt->post['title'])));
        $this->line($this->green($prompt->post['url']));
        $this->line($this->dim(str_repeat('─', $this->scrollWidth)));

        $this->newLine();

        $this->scrollbar(
            visible: collect($finalLines)->slice($firstVisible, $height),
            firstVisible: $firstVisible,
            total: count($finalLines),
            height: $height,
            width: $this->scrollWidth,
        )->each(fn ($line) => $this->line($line));

        $this->newLine(2);

        $this->hotkey('b', 'Back to posts');
        $this->hotkey('q', 'Quit');

        foreach ($this->hotkeys() as $hotkey) {
            $this->line($hotkey);
        }

        return $this;
    }

    protected function renderBrowseState(Blog $prompt): static
    {
        $this->line($this->cyan($this->bold("Joe Tannenbaum's Blog")));
        $this->line($this->dim('https://blog.joe.codes · https://twitter.com/joetannenbaum'));

        $this->newLine(2);

        if ($prompt->fetching) {
            $frame = $this->spinnerFrames[$prompt->spinnerCount % count($this->spinnerFrames)];

            $this->line($this->magenta($frame) . ' Fetching posts...');

            return $this;
        }

        foreach ($prompt->posts[$prompt->browsePage] as $index => $post) {
            if ($index === $prompt->browseSelected) {
                $this->line($this->green($post['date']));
                $this->line($this->bold($this->cyan($post['title'])));
            } else {
                $this->line($this->dim($post['date']));
                $this->line($post['title']);
            }

            $this->newLine();
        }

        if (count($prompt->posts) > 1) {
            $dots = Util::range(1, count($prompt->posts))
                ->map(fn ($page) => $page === $prompt->browsePage + 1 ? $this->green('•') : $this->dim('•'))
                ->join(' ');

            $this->line($dots);
            $this->newLine(2);

            $this->hotkey('←', 'Previous page', $prompt->browsePage > 0);
            $this->hotkey('→', 'Next page', $prompt->browsePage < count($prompt->posts) - 1);
        }

        $this->hotkey('↑ ↓', 'Change selection');
        $this->hotkey('Enter', 'Select');
        $this->hotkey('q', 'Quit');

        foreach ($this->hotkeys() as $hotkey) {
            $this->line($hotkey);
        }

        return $this;
    }

    protected function boxInternal(string $title, string $content): string
    {
        $this->box($title, $content);

        $box = $this->output;

        $this->output = '';

        return $box;
    }

    protected function drawButtons(array $block): array
    {
        return collect($block['buttons'])->map(
            fn ($button) => $this->green($button['button_label']) . ' (' . $this->cyan($button['button_url']) . ')'
        )->toArray();
    }

    protected function drawYouTubeVideo(array $block): array
    {
        preg_match('/embed\/([a-zA-Z0\-_]+)"/', $block['youtube_video_embed_code'], $matches);

        return explode(PHP_EOL, $this->boxInternal('Youtube', 'https://www.youtube.com/watch?v=' . $matches[1]));
    }

    protected function drawTweet(array $block): array
    {
        $converter = new HtmlConverter([
            'strip_tags' => true,
        ]);

        $tweet = $converter->convert($block['tweet_embed_code']);

        $tweet = html_entity_decode($tweet);

        $tweet = collect(explode(PHP_EOL, $tweet))
            ->map(fn ($line) => ltrim($line, '> '))
            ->join(PHP_EOL);

        $tweet = wordwrap($tweet, $this->maxLineLength - 6, PHP_EOL, true);

        return explode(PHP_EOL, $this->boxInternal('Tweet', $tweet));
    }

    protected function drawCallout(array $block): array
    {
        $converter = new HtmlConverter([
            'strip_tags' => true,
        ]);

        $content = $converter->convert($block['callout']);

        $content = html_entity_decode($content);

        $content = collect(explode(PHP_EOL, $content))
            ->map(fn ($line) => ltrim($line, '> '))
            ->join(PHP_EOL);

        $content = wordwrap($content, $this->maxLineLength - 6, PHP_EOL, true);

        return explode(PHP_EOL, $this->boxInternal(ucwords($block['icon']), $content));
    }

    protected function drawCodeBlock(array $block): array
    {
        return [];

        $content = wordwrap($block['code']['code'], $this->maxLineLength - 6, PHP_EOL, true);

        return explode(PHP_EOL, $this->boxInternal('Code', $content));
    }

    protected function drawTorchlight(array $block): array
    {
        $content = str_replace("\t", '    ', $block['content']);
        $content = wordwrap($content, $this->maxLineLength - 6, PHP_EOL, true);

        return explode(PHP_EOL, $this->boxInternal('Code', $content));
    }

    protected function drawVideo(array $block): array
    {
        return explode(PHP_EOL, $this->boxInternal('Video', $block['video']['permalink']));
    }

    protected function drawImage(array $block): array
    {
        return explode(PHP_EOL, $this->boxInternal('Image', $block['image']['permalink']));
    }

    protected function drawText(array $block): array
    {
        $converter = new HtmlConverter();

        $converter->getEnvironment()->addConverter(new class implements \League\HTMLToMarkdown\Converter\ConverterInterface
        {
            public function convert(\League\HTMLToMarkdown\ElementInterface $element): string
            {
                if ($element->getTagName() === 'a') {
                    return $this->tag('GREEN', $element->getValue()) . ' (' . $this->tag('CYAN', $element->getAttribute('href')) . ')';
                }

                return match ($element->getTagName()) {
                    'strong', 'b' => $this->tag('BOLD', $element->getValue()),
                    'em', 'i' => $this->tag('ITALIC', $element->getValue()),
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => $this->tag(
                        'MAGENTA',
                        $this->tag(
                            'BOLD',
                            str_repeat('#', ltrim($element->getTagName(), 'h')) . ' ' . $element->getValue()
                        )
                    ) . PHP_EOL . PHP_EOL,
                    default => $element->getValue(),
                };
            }

            protected function start(string $name): string
            {
                return "INTERNAL_{$name}_START";
            }

            protected function end(string $name): string
            {
                return "INTERNAL_{$name}_END";
            }

            protected function tag(string $tag, $content): string
            {
                return $this->start($tag) . $content . $this->end($tag);
            }

            public function getPriority(): int
            {
                return 0;
            }

            public function getSupportedTags(): array
            {
                return ['strong', 'em', 'b', 'i', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            }
        });

        $text = Str::of($converter->convert($block['text']))
            ->replace(['INTERNAL_BOLD_START', 'INTERNAL_BOLD_END'], ["\e[1m", "\e[22m"])
            ->replace(['INTERNAL_ITALIC_START', 'INTERNAL_ITALIC_END'], ["\e[3m", "\e[22m"])
            ->replace(['INTERNAL_GREEN_START', 'INTERNAL_GREEN_END'], ["\e[32m", "\e[39m"])
            ->replace(['INTERNAL_CYAN_START', 'INTERNAL_CYAN_END'], ["\e[36m", "\e[39m"])
            ->replace(['INTERNAL_MAGENTA_START', 'INTERNAL_MAGENTA_END'], ["\e[35m", "\e[39m"])
            ->toString();

        $text = html_entity_decode($text);

        return explode(PHP_EOL, $text);
    }
}
