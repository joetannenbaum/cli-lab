<?php

namespace App\Http\Integrations\SpotifyApi\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetPlaylist extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public string $id,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return '/playlists/' . $this->id;
    }
}
