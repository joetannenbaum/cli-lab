<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use App\Lab\PhpXNyc as LabPhpXNyc;
use App\Lab\Resume as LabResume;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Prompts\Concerns\Colors;

use function Laravel\Prompts\info;
use function Laravel\Prompts\text;

class PhpXNyc extends Command implements LabCommand
{
    use Colors;

    protected $signature = 'lab:php-x-nyc';

    protected $description = 'RSVP for PHP × NYC event.';

    public function handle()
    {
        $this->runLab();
    }

    public function runLab($internal = false): void
    {
        $this->output->write("\e[?1049h");


        $this->newLine();

        $this->output->writeln($this->indent($this->bold($this->cyan('PHP × NYC'))));

        $this->newLine();

        $this->output->writeln($this->indent([
            'A fresh PHP meetup for NYC area devs.',
            'Meet. Learn. Eat. Drink.',
        ]));

        $this->newLine();

        $this->output->writeln($this->indent([
            'Our first meetup is happening on:',
            '',
            $this->bold('February 29, 2024'),
            $this->bold('Midtown Manhattan'),
            $this->bold('6:30pm - 9:30pm'),
            '',
            'Location and speakers will be announced soon.',
            '',
            'We\'d love to see you there.',
            '',
            'Fill out the form below if you\'d like to join.',
        ]));

        $this->newLine();

        $name = text(
            label: 'Name',
            required: true,
        );

        $email = text(
            label: 'Email',
            required: true,
            validate: function ($value) {
                $validator = Validator::make(
                    ['email' => $value],
                    ['email' => ['required', 'email']],
                );

                if ($validator->fails()) {
                    return $validator->errors()->first();
                }

                return null;
            }
        );

        Log::info('Attendee form submitted', [
            'name' => $name,
            'email' => $email,
        ]);

        $name = explode(' ', $name);
        $firstName = array_shift($name);
        $lastName = implode(' ', $name);

        $pendingRequest = Http::withToken(config('services.mailcoach.api_token'))
            ->acceptJson()
            ->asJson()
            ->baseUrl('https://joecodes.mailcoach.app/api/');

        $existing = $pendingRequest->get(
            'email-lists/b1f0738e-ab7a-48e7-a454-91597ef06fe0/subscribers',
            ['filter[email]' => $email],
        );

        $params = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'tags' => ['meetup-2024-02-29'],
        ];

        if (count($existing->json('data')) > 0) {
            $pendingRequest->patch(
                'subscribers/' . $existing->json('data.0.uuid'),
                array_merge($params, ['append_tags' => true]),
            );
        } else {
            $pendingRequest->post(
                'email-lists/b1f0738e-ab7a-48e7-a454-91597ef06fe0/subscribers',
                $params,
            );
        }

        info($this->indent("Got it! We'll see you there. Can't wait to meet you."));

        $this->output->write($this->indent(' Closing in  '));

        $i = 5;

        while ($i > 0) {
            $this->output->write("\033[1D");
            $this->output->write($i);
            sleep(1);
            $i--;
        }
    }

    protected function indent(string|array $value)
    {
        $spaces = ' ';

        if (is_string($value)) {
            return $spaces . $value;
        }

        return collect($value)->map(fn ($line) => $spaces . $line)->toArray();
    }

    public function __destruct()
    {
        if ($this->output) {
            $this->output->write("\e[?1049l");
        }
    }
}
