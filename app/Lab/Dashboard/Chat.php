<?php

namespace App\Lab\Dashboard;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Loopable;
use Illuminate\Support\Collection;

class Chat implements Loopable
{
    use Ticks;

    public $currentMessage = '';

    public $message = '';

    public $speaker = '';

    public Collection $messages;

    protected Collection $pendingMessages;

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

        if ($this->currentMessage === $this->message) {
            $this->messages->push([$this->speaker, $this->message]);
            $this->nextMessage();

            return;
        }

        $this->currentMessage = substr($this->message, 0, strlen($this->currentMessage) + 1);
    }

    protected function loadConversation()
    {
        $data = file_get_contents(storage_path('ascii/chat.txt'));

        $this->pendingMessages = collect(explode(PHP_EOL, $data))
            ->filter()
            ->map(fn ($line) => explode(':', $line))
            ->map(fn ($parts) => array_map('trim', $parts))
            ->values();
    }

    protected function nextMessage()
    {
        [$speaker, $message] = $this->pendingMessages->shift();

        $this->currentMessage = '';
        $this->message = $message;
        $this->speaker = $speaker;
        $this->pauseFor(10);
    }
}
