<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/sass/app.scss', 'resources/js/app.js'])
    <title>{{ config('app.name') }}</title>
</head>

<body>
    {{ $slot ?? '' }}
    @yield('content')
</body>

</html>
