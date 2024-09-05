<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta id="theme-color" name="theme-color" content="#fff">


</head>

<body>

    <div id="container">
        <div class="select">
            <label for="audioSource">Audio source: </label><select id="audioSource"></select>
        </div>

        <div class="select">
            <label for="videoSource">Video source: </label><select id="videoSource"></select>
        </div>

        <video autoplay muted playsinline></video>

        <script async src="js/main.js"></script>

        <canvas></canvas>
        {{--
        <p>For more information see <a href="https://www.html5rocks.com/en/tutorials/getusermedia/intro/"
                title="Media capture article by Eric Bidelman on HTML5 Rocks">Capturing Audio &amp; Video in HTML5</a>
            on HTML5 Rocks.</p>

        <a href="https://github.com/samdutton/simpl/blob/gh-pages/getusermedia/sources/js/main.js"
            title="View source for this page on GitHub" id="viewSource">View source on GitHub</a> --}}

    </div>

    <script src="/js/terminal-video.js"></script>
</body>

</html>
