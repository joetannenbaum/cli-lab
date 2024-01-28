<?php

declare(strict_types=1);

namespace App\Lab\Integrations;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Saloon\Http\Auth\AccessTokenAuthenticator;

class Spotify
{
    public function __construct(protected string $key)
    {
        Cache::put('spotify:generated_token:' . $this->key, CarbonInterval::day());
    }

    public static function validKey(string $key): bool
    {
        return Cache::has("spotify:generated_token:{$key}");
    }

    public function storeAuthenticator($authenticator)
    {
        Cache::put("spotify:{$this->key}:authenticator", $authenticator->serialize(), CarbonInterval::day());
    }

    public function connected(): bool
    {
        return Cache::has("spotify:{$this->key}:authenticator");
    }

    public function forgetKey()
    {
        Cache::forget("spotify:generated_token:{$this->key}");
    }

    public function forget()
    {
        Cache::forget("spotify:{$this->key}:authenticator");
    }

    public function authenticator(): AccessTokenAuthenticator
    {
        return AccessTokenAuthenticator::unserialize(Cache::get("spotify:{$this->key}:authenticator"));
    }
}
