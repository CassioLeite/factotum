<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Início') — {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="hidden border-r border-slate-200 bg-white lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col">
                <div class="px-6 py-6">
                    <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                        {{ config('app.name') }}
                    </a>
                    <p class="mt-1 text-sm text-slate-500">Automação de mensagens</p>
                </div>
                @include('partials.nav')
            </aside>

            <div class="min-w-0">
                <header class="border-b border-slate-200 bg-white lg:hidden">
                    <div class="flex items-center justify-between px-4 py-4">
                        <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">
                            {{ config('app.name') }}
                        </a>
                    </div>
                    <details class="border-t border-slate-200">
                        <summary class="cursor-pointer list-none px-4 py-3 text-sm font-medium text-slate-700 [&::-webkit-details-marker]:hidden">
                            Menu
                        </summary>
                        <div class="px-2 pb-3">
                            @include('partials.nav')
                        </div>
                    </details>
                </header>

                <main class="mx-auto max-w-5xl p-4 sm:p-6 lg:p-8">
                    @include('partials.flash')
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
