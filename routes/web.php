<?php

use App\Http\Integrations\Spotify\Spotify;
use App\Lab\Integrations\Spotify as IntegrationsSpotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

Route::get('/', function () {
    return view('welcome');
});

Route::get('spotify/auth/{token}', function (string $token, Spotify $spotify) {
    if (!IntegrationsSpotify::validKey($token)) {
        return redirect('/');
    }

    $url = $spotify->getAuthorizationUrl();

    session()->put('spotify:generated_token', $token);
    session()->put('spotify:state', $spotify->getState());

    return redirect($url);
})->name('spotify.auth');

Route::get('spotify/callback', function (Request $request, Spotify $spotify) {
    $validator = Validator::make($request->all(), [
        'code'  => 'required',
        'state' => 'required',
    ]);

    if ($validator->fails()) {
        return redirect('/');
    }

    $authenticator = $spotify->getAccessToken(
        code: $request->input('code'),
        state: $request->input('state'),
        expectedState: session()->pull('spotify:state'),
    );

    $authKey = session()->pull('spotify:generated_token');

    file_put_contents(storage_path('spotify-auth'), $authenticator->serialize());

    (new IntegrationsSpotify($authKey))->storeAuthenticator($authenticator);

    return view('spotify-authed');
})->name('spotify.callback');
