<?php

namespace App\Lab\Dashboard;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Illuminate\Support\Collection;

class Chat implements Loopable
{
    use Ticks;

    public $currentlyTyping = '';

    public $message = '';

    public $speaker = '';

    public Collection $messages;

    protected Collection $toType;

    public function __construct()
    {
        $this->messages = collect();

        $this->loadConversation();
    }

    public function onTick(): void
    {
        if ($this->message === '') {
            $this->nextMessage();

            return;
        }

        if ($this->speaker === 'HAL') {
            $this->onNthTick(10, function () {
                // Hal doesn't type... but does he show a loading indicator?
                $this->messages->push([$this->speaker, $this->message]);
                $this->nextMessage();
            });

            return;
        }

        if ($this->currentlyTyping === $this->message) {
            $this->messages->push([$this->speaker, $this->message]);
            $this->nextMessage();

            return;
        }

        $this->currentlyTyping = substr($this->message, 0, strlen($this->currentlyTyping) + 1);
    }

    protected function loadConversation()
    {
        $data = file_get_contents(storage_path('ascii/chat.txt'));

        $this->toType = collect(explode(PHP_EOL, $data))
            ->filter()
            ->map(fn ($line) => explode(':', $line))
            ->map(fn ($parts) => array_map('trim', $parts))
            ->values();
    }

    protected function nextMessage()
    {
        [$speaker, $message] = $this->toType->shift();

        $this->currentlyTyping = '';
        $this->message = $message;
        $this->speaker = $speaker;
        $this->pauseFor(10);
    }
}
