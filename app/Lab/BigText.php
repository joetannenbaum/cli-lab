<?php

namespace App\Lab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\RegistersThemes;
use Chewie\Concerns\SetsUpAndResets;
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
}
