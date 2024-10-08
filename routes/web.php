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

    file_put_contents(storage_path('spotify-auth'), $authenticator->serialize());

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
            ->map(fn($item) => [
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

Route::get('terminal/video', function () {
    return view('terminal-video');
});

Route::post('terminal/video', function (Request $request) {
    file_put_contents(
        storage_path('terminal-video/' . now()->toDateTimeString('microseconds') . '.jpg'),
        $request->getContent(),
    );
});

Route::get('laracon-us/{channel_id}', function ($channel_id) {
    return view('laracon-us', [
        'channel_id' => $channel_id,
    ]);
});


Route::get('art-class/{id}', function ($id) {
    $id = collect(explode('/', $id))->last();

    $artClass = json_decode(file_get_contents(storage_path('art-class/' . $id . '.json')));

    $im = imagecreatetruecolor($artClass->width * 20, $artClass->height * 20);

    $colors = [
        'black' => imagecolorallocate($im, 0, 0, 0),
        'red' => imagecolorallocate($im, 255, 0, 0),
        'green' => imagecolorallocate($im, 0, 255, 0),
        'yellow' => imagecolorallocate($im, 255, 255, 0),
        'blue' => imagecolorallocate($im, 0, 0, 255),
        'magenta' => imagecolorallocate($im, 255, 0, 255),
        'cyan' => imagecolorallocate($im, 0, 255, 255),
        'white' => imagecolorallocate($im, 255, 255, 255),
    ];

    foreach ($artClass->art as $y => $row) {
        foreach ($row as $x => $color) {
            imagefilledrectangle($im, $x * 20, $y * 20, ($x + 1) * 20, ($y + 1) * 20, $colors[$color]);
        }
    }

    $text = 'created at ssh cli.lab.joe.codes -t art-class';

    $font = 24;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $textX = 10;
    $textY = $artClass->height * 20 - $textHeight - 10;
    imagettftext($im, $font, 0, $textX, $textY, $colors['white'], storage_path('fonts/FiraCode-Regular.ttf'), $text);

    imagepng($im, storage_path('art-class/' . $id . '.png'));

    return response()->download(storage_path('art-class/' . $id . '.png'), $id . '.png');
});
