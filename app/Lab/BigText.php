<?php

namespace App\Lab;

use App\Lab\Concerns\CreatesAnAltScreen;
use App\Lab\Concerns\RegistersThemes;
use App\Lab\Concerns\SetsUpAndResets;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class BigText extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public string $message = "Type a messsage,\nenter to clear";

    public function __construct()
    {
        $this->registerTheme();

        $this->createAltScreen();

        $this->on('key', function ($key) {
            if ($key === Key::ENTER) {
                $this->message = '';
            } elseif ($key === Key::BACKSPACE) {
                $this->message = substr($this->message, 0, -1);
            } elseif ($key === Key::CTRL_C) {
                $this->terminal()->exit();
            } else {
                $key = strtolower($key);

                $valid = array_merge(range('a', 'z'), [' '], ['.', ',', '?', '!', "'"]);

                if (in_array($key, $valid)) {
                    $this->message .= $key;
                }
            }
        });
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function value(): mixed
    {
        return null;
    }

    protected function loadAscii()
    {
        $alpha = file_get_contents(storage_path('ascii/alphabet/all.txt'));

        $letters = range('a', 'z');

        $alpha = collect(explode("\n\n", $alpha))->map(fn ($letter, $i) => file_put_contents(storage_path("ascii/alphabet/{$letters[$i]}.txt"), $letter));
    }
}
