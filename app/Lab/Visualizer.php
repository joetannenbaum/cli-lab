<?php

namespace App\Lab;

use App\Http\Integrations\SpotifyApi\Requests\GetDevices;
use App\Http\Integrations\SpotifyApi\Requests\GetTrackAnalysis;
use App\Http\Integrations\SpotifyApi\Requests\PauseTrack;
use App\Http\Integrations\SpotifyApi\Requests\PlayTrack;
use App\Http\Integrations\SpotifyApi\Requests\PlayTracks;
use App\Http\Integrations\SpotifyApi\SpotifyApi;
use App\Lab\Renderers\VisualizerRenderer;
use App\Lab\Visualizer\Average;
use App\Lab\Visualizer\Loudness;
use Chewie\Concerns\Loops;
use Chewie\Concerns\RegistersRenderers;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Prompt;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class Visualizer extends Prompt
{
    use RegistersRenderers;
    use Loops;
    use SetsUpAndResets;

    protected SpotifyApi $spotify;

    public Loudness $loudnessMax;

    public Loudness $loudnessStart;

    public Average $pitch1;

    public Average $pitch2;

    public Average $pitch3;

    public Average $timbre1;

    public Average $timbre2;

    public Average $timbre3;

    protected $speed = 2;

    protected $track = '0pJO1tc1GpnxFyQp6Zp82r'; // "Gorilla" - Lil Simz
    // protected $track = '2dSKFFNoNXKo3hPnlwUdPe'; // "They Don't Love It - Jack Harlow"

    public function __construct()
    {
        $this->registerRenderer(VisualizerRenderer::class);

        $this->spotify = new SpotifyApi();
        $authenticator = AccessTokenAuthenticator::unserialize(file_get_contents(storage_path('spotify-auth')));
        $this->spotify->authenticate($authenticator);

        // $this->loadEm();

        $info = File::json(storage_path('visualizer/' . $this->track . '-simple.json'));

        $this->loudnessMax = new Loudness($info, 'loudness_max', $this->speed);
        $this->loudnessStart = new Loudness($info, 'loudness_start', $this->speed);
        $this->pitch1 = new Average($info, 'pitches', 0, $this->speed);
        $this->pitch2 = new Average($info, 'pitches', 1, $this->speed);
        $this->pitch3 = new Average($info, 'pitches', 2, $this->speed);
        $this->timbre1 = new Average($info, 'timbre', 0, $this->speed);
        $this->timbre2 = new Average($info, 'timbre', 1, $this->speed);
        $this->timbre3 = new Average($info, 'timbre', 2, $this->speed);

        $this->registerLoopables(
            $this->loudnessMax,
            $this->loudnessStart,
            $this->pitch1,
            $this->pitch2,
            $this->pitch3,
            $this->timbre1,
            $this->timbre2,
            $this->timbre3,
        );
    }

    public function run()
    {
        $this->setup($this->visualize(...));
    }

    public function visualize()
    {
        $listener = KeyPressListener::for($this)->listenForQuit();

        $response = $this->spotify->send(new PlayTracks(
            ['spotify:track:' . $this->track],
            'ad9e2c4680981db31b770ff759428cddb7c1c40c'
        ));

        // Give it a second to start the track
        // usleep(100_000);

        $this->loop(function () use ($listener) {
            $this->render();
            $listener->once();
        }, $this->speed * 1000);
    }

    public function __destruct()
    {
        $this->spotify->send(new PauseTrack());
    }

    protected function loadEm()
    {
        $response = $this->spotify->send(new GetTrackAnalysis($this->track));

        file_put_contents(storage_path('visualizer/' . $this->track . '.json'), json_encode($response->json(), JSON_PRETTY_PRINT));

        $response = json_decode(file_get_contents(storage_path('visualizer/' . $this->track . '.json')), true);

        $segments = $response['segments'];

        $segments = collect($segments)->map(fn ($segment) => Arr::only($segment, [
            'start',
            'duration',
            'loudness_start',
            'loudness_max',
            'loudness_end',
            'pitches',
            'timbre',
        ]));

        file_put_contents(storage_path('visualizer/' . $this->track . '-simple.json'), $segments->toJson(JSON_PRETTY_PRINT));
    }

    public function value(): mixed
    {
        return null;
    }
}
