<?php

declare(strict_types=1);

namespace App\Lab\Integrations;

use Illuminate\Support\Facades\Http;

class Fathom
{
    public static function track($class)
    {
        if (!config('services.fathom_analytics.api_key')) {
            return;
        }

        $response = Http::asJson()->acceptJson()->withToken(config('services.fathom_analytics.api_key'))->post(
            'https://api.usefathom.com/v1/sites/' . config('services.fathom_analytics.ssh_site_id') . '/events',
            [
                'name' => collect(explode('\\', get_class($class)))->last(),
            ],
        );

        dd($response->json());
    }
}
