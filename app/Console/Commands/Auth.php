<?php

namespace App\Console\Commands;

use App\Contracts\LabCommand;
use Carbon\Carbon;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;
use Illuminate\Support\Str;

class Auth extends Command implements LabCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lab:auth {--key=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function runLab(): void
    {
        //
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = collect([
            [
                'id' => 1,
                'name' => 'Joe',
                'email' => 'joe@joe.codes',
                'password' => 'asdfasdf',
            ],
            [
                'id' => 2,
                'name' => 'Gary',
                'email' => 'gary@joe.codes',
                'password' => 'asdfasdf',
            ]
        ]);

        if ($this->option('key')) {
            $userId = collect(explode(' ', $this->option('key')))->last();
            $user = $users->firstWhere('id', $userId);

            if ($user) {
                $this->newLine();
                $this->info('👋 Hi, ' . $user['name'] . '!');
                if (!confirm('Continue to app?', false)) {
                    return;
                }
            } else {
                $this->error('Invalid key');
                return;
            }
        }

        $email = text(
            label: 'Email',
            required: true,
        );

        $password = password(
            label: 'Password',
        );

        $user = $users->firstWhere('email', $email);

        $this->info('👋 Hi, ' . $user['name'] . '!');

        $generateKey = confirm(
            label: 'Generate SSH key?',
            hint: 'Use this key instead of your password in the future',
        );

        $localKeyName = text(
            label: 'Local key name',
            hint: 'This is the name of the key file on your local machine',
            default: str_replace('-', '_', Str::slug('auth ' . $user['email'] . ' ' . Carbon::now()->timestamp)),
        );

        $keyFile = storage_path('keys/' . Str::uuid());

        exec(
            sprintf(
                'ssh-keygen -t ed25519 -C "%s" -f %s -N ""',
                $user['id'],
                $keyFile
            ),
        );

        $publicKey = file_get_contents($keyFile . '.pub');
        file_put_contents(base_path('authorized_keys_cli_lab'), $publicKey . PHP_EOL, FILE_APPEND);


        $privateKey = file_get_contents($keyFile);

        $this->info(str_repeat('-', 80));
        $this->newLine();
        $this->info('Copy and paste this into your terminal:');
        $this->newLine();
        $this->info(
            sprintf(
                'echo "%s" > %s && chmod 600 %s',
                str_replace("\n", '\n', $privateKey),
                $localKeyName,
                $localKeyName,
            )
        );
        $this->newLine();
        $this->info(str_repeat('-', 80));

        unlink($keyFile);
        unlink($keyFile . '.pub');
    }
}
