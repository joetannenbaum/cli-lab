<?php

namespace App\Lab\Sticker;

use Chewie\Input\KeyPressListener;
use Illuminate\Support\Facades\Validator;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;

class Input
{
    use TypedValue;
    use Colors;

    public bool $isFocused = false;

    public bool $isValid = true;

    protected KeyPressListener $listener;

    protected array $listeners = [];

    public array $errors = [];

    public function __construct(
        public string $label,
        public string $key,
        public array $validation = [],
        public string $hint = '',
    ) {
        //
    }

    public function listener(
        KeyPressListener $listener,
        array $listeners = []
    ) {
        $this->listener = $listener;
        $this->listeners = $listeners;

        return $this;
    }

    public function focus()
    {
        $this->isFocused = true;

        $this->listener
            ->clearExisting()
            ->listenToInput($this->typedValue, $this->cursorPosition);

        collect($this->listeners)->each(fn ($listener) => $this->listener->on($listener[0], $listener[1]));

        $this->listener->listen();
    }

    public function unfocus()
    {
        $this->isFocused = false;

        $this->validate();
    }

    public function validate()
    {
        $result = Validator::make(
            [$this->label => $this->typedValue],
            [$this->label => $this->validation],
            [
                'required' => 'This field is required.',
                'url' => 'This field must be a valid URL.',
            ]
        );

        $this->isValid = $result->passes();

        $this->errors = $result->errors()->all();
    }

    public function valueWithCursor(int $maxWidth): string
    {
        if (!$this->isFocused) {
            return $this->dim($this->typedValue, 0, $maxWidth);
        }

        return $this->addCursor($this->typedValue, $this->cursorPosition, $maxWidth);
    }
}
