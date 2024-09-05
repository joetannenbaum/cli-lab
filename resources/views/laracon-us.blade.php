<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta id="theme-color" name="theme-color" content="#fff">

    <link href="https://fonts.bunny.net/css?family=space-mono:400,400i,700,700i" rel="stylesheet" />

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: 'Space Mono',
                    },
                },
            },
        };
    </script>

    <script>
        window.channelId = '{{ $channel_id }}';
    </script>

    @vite(['resources/js/laracon-us.js'])
</head>

<body class="flex items-center justify-center h-screen font-mono text-white bg-zinc-900">
    <button class="px-4 py-2 transition-all border border-white rounded hover:border-white/50">start listening</button>

    <div id="pulse" class="hidden transition-all bg-white rounded-full size-12"></div>

    <video controls preload="auto" class="hidden">
        <source src="https://grandmas-house-entertainment-offsite-backup.nyc3.digitaloceanspaces.com/talk.mp4"
            type="video/mp4">
    </video>
</body>

</html>
