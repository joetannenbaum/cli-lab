<?php

namespace App\Http\Integrations\SpotifyApi\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetDevices extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/me/player/devices';
    }
}
