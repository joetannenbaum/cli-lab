<?php

namespace App\Http\Integrations\SpotifyApi;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class SpotifyApi extends Connector
{
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return 'https://api.spotify.com/v1/';
    }
}
