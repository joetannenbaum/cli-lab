<?php

namespace App\Http\Integrations\Spotify;

use Saloon\Helpers\OAuth2\OAuthConfig;
use Saloon\Http\Connector;
use Saloon\Traits\OAuth2\AuthorizationCodeGrant;
use Saloon\Traits\Plugins\AcceptsJson;

class Spotify extends Connector
{
    use AuthorizationCodeGrant;
    use AcceptsJson;

    public function resolveBaseUrl(): string
    {
        return 'https://accounts.spotify.com/';
    }

    protected function defaultOauthConfig(): OAuthConfig
    {
        return OAuthConfig::make()
            ->setClientId(config('services.spotify.client_id'))
            ->setClientSecret(config('services.spotify.client_secret'))
            ->setRedirectUri(config('services.spotify.redirect_uri'))
            ->setDefaultScopes([
                'playlist-read-private',
                'playlist-read-collaborative',
                'user-read-playback-state',
                'user-modify-playback-state',
                'user-read-currently-playing',
            ])
            ->setAuthorizeEndpoint('authorize')
            ->setTokenEndpoint('api/token')
            ->setUserEndpoint('user');
    }
}
