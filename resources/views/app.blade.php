<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>POS System</title>
    <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}" />
    <meta name="theme-color" content="#1E6FEB" />
    @vite(['resources/css/app.css', 'resources/js/main.js'])
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/build/sw.js');
            });
        }
    </script>
</head>
<body class="h-full font-sans antialiased bg-pos-bg text-gray-900">
    <div id="app"></div>
</body>
</html>
