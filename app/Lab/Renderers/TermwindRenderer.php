<?php

namespace App\Lab\Renderers;

use App\Lab\Termwind;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Output\BufferedOutput;

use function Termwind\render;
use function Termwind\renderUsing;

class TermwindRenderer extends Renderer
{
    public function __invoke(Termwind $prompt): string
    {
        $html = view('termwind', [
            'prompt' => $prompt,
        ])->render();

        $output = new BufferedOutput();

        renderUsing($output);

        render($html);

        dd($output);

        // $this->output = $output->fetch();

        ray($html, $this->output);

        return $this;
    }
}
