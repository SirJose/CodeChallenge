<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name', 'Code Challenge') }}</title>

    {{-- SCRIPTS --}}
    @vite([
        'resources/js/app.js',
        'resources/sass/app.scss'
    ])

</head>
<body>

    {{-- NAVBAR --}}
    @include('includes.navbar');

    {{-- MAIN CONTENT --}}
    @yield('content')

    {{-- SCRIPTS --}}
    @yield('scripts')

</body>
</html>
