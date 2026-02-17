<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Ziggy routes -->
    @routes

    @vite('resources/js/app.js')

</head>
<body class="font-sans antialiased bg-gray-100 text-xs">
@inertia
</body>
</html>
