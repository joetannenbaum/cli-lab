<?php

namespace App\Http\Integrations\SpotifyApi\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class GetTrackAnalysis extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        public string $trackId,
    ) {
        //
    }

    public function resolveEndpoint(): string
    {
        return '/audio-analysis/' . $this->trackId;
    }
}
