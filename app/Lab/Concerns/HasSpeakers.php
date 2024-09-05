<?php

namespace App\Lab\Concerns;

use Illuminate\Support\Collection;

trait HasSpeakers
{
    protected function loadSpeakers($fullBio = false): Collection
    {
        $speakers = json_decode(file_get_contents(storage_path('laracon/india/speakers.json')), true);

        return collect($speakers)->map(function ($speaker) use ($fullBio) {
            $bioPath = storage_path('laracon/india/' . $speaker['twitter']);

            if ($fullBio) {
                $bioPath .= '-full';
            }

            $bioPath .= '.txt';

            $speaker['bio'] = collect(explode(PHP_EOL, file_get_contents($bioPath)))
                ->filter()
                ->implode(PHP_EOL . PHP_EOL);

            $speaker['twitter'] = 'https://twitter.com/' . $speaker['twitter'];

            return $speaker;
        });
    }
}
