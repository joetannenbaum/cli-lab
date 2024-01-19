<?php

namespace App\Http\Integrations\SpotifyApi\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPlaylists extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/me/playlists';
    }

    protected function defaultQuery(): array
    {
        return [
            'limit' => 50,
        ];
    }
}
