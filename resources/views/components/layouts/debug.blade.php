@props([
    'title' => 'Debug'
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="h-screen overflow-hidden bg-white dark:bg-zinc-900">
        <main class="h-full">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
