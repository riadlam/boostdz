<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'BOOSTDZ') }} — Real Likes, Followers &amp; Views</title>
        <meta name="description" content="Buy real likes, followers, and views for Instagram, TikTok, YouTube, and X. Instant delivery. No password required.">
        <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <script>
            (function () {
                try {
                    var t = localStorage.getItem('boostdz-color-mode');
                    if (t === 'dark') document.documentElement.classList.add('dark');
                    else document.documentElement.classList.remove('dark');
                } catch (e) {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased">
        <div id="root"></div>
    </body>
</html>
