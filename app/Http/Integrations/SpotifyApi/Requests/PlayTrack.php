<?php

namespace App\Http\Integrations\SpotifyApi\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class PlayTrack extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        public string $contextUri,
        public int $offset,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return '/me/player/play';
    }

    protected function defaultBody(): array
    {
        return [
            'context_uri' => $this->contextUri,
            'position_ms' => 0,
            'offset' => [
                'position' => $this->offset,
            ],
        ];
    }
}
