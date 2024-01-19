<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚗️</text></svg>">

    <title>CLI Lab by Joe Tannenbaum</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-mono:400,400i,700,700i" rel="stylesheet" />

    <script defer src="https://cdn.jsdelivr.net/npm/@ryangjchandler/alpine-clipboard@2.x.x/dist/alpine-clipboard.js">
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['Space Mono', 'monospace'],
                    }
                }
            }
        }
    </script>

    <style>
        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }

        .animate-blink {
            animation: blink 2s infinite;
        }
    </style>

    @include('partials.fathom_analytics')
</head>

<body class="font-mono antialiased text-white bg-slate-950">
    <div class="flex flex-col items-center justify-center h-screen">
        <div class="text-center">
            <pre class="inline-block text-xs text-sky-300 md:text-base">
 ______  __      __       __      ______  ______
/\  ___\/\ \    /\ \     /\ \    /\  __ \/\  == \
\ \ \___\ \ \___\ \ \    \ \ \___\ \  __ \ \  __<
  \ \_____\ \_____\ \_\    \ \_____\ \_\ \_\ \_____\
   \/_____/\/_____/\/_/     \/_____/\/_/\/_/\/_____/
</pre>
        </div>

        <div class="px-4 mt-12 text-base text-center md:text-3xl text-balace">
            All set! You can close this window and head back to the terminal.
        </div>
    </div>
</body>

</html>