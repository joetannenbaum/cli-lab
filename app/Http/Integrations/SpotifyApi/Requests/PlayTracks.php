<?php

namespace App\Http\Integrations\SpotifyApi\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class PlayTracks extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        public array $uris,
        public ?string $deviceId = null,
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
            'uris' => $this->uris,
            'position_ms' => 0,
            'device_id' => $this->deviceId,
        ];
    }
}
