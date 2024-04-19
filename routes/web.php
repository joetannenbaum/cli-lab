<?php

use App\Http\Integrations\Spotify\Spotify;
use App\Lab\Integrations\Spotify as IntegrationsSpotify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Stripe\StripeClient;

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

    (new IntegrationsSpotify($authKey))->storeAuthenticator($authenticator);

    return view('spotify-authed');
})->name('spotify.callback');

Route::get('shop/checkout/{cartKey}', function (string $cartKey) {
    $cart = Cache::pull('shop:cart:' . $cartKey);

    if (!$cart) {
        abort(404);
    }

    $stripe = new StripeClient(config('services.stripe.key'));

    $checkout = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items'           => collect($cart)
            ->map(fn ($item) => [
                'price_data' => [
                    'currency'     => 'usd',
                    'product_data' => [
                        'name' => sprintf('%s (%s)', $item['product']['name'], $item['product']['category']),
                    ],
                    'unit_amount' => $item['product']['price'] * 100,
                ],
                'quantity'   => $item['quantity'],
            ])
            ->values()
            ->toArray(),
        'mode'                 => 'payment',
        'success_url'          => url('/shop/success'),
        'cancel_url'           => url('/shop/cancel'),
    ]);

    return redirect($checkout->url);
})->name('shop.checkout');
