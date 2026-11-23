@php
    $links = [
        ['label' => 'Início', 'href' => route('home'), 'active' => request()->routeIs('home')],
        ['label' => 'Contatos', 'href' => route('contacts.index'), 'active' => request()->routeIs('contacts.*')],
        ['label' => 'Obrigações', 'href' => route('modules.show', 'obligations'), 'active' => request()->routeIs('modules.show') && request()->route('module') === 'obligations'],
        ['label' => 'Mensagens', 'href' => route('modules.show', 'messages'), 'active' => request()->routeIs('modules.show') && request()->route('module') === 'messages'],
        ['label' => 'Modelos', 'href' => route('modules.show', 'templates'), 'active' => request()->routeIs('modules.show') && request()->route('module') === 'templates'],
        ['label' => 'Configurações', 'href' => route('modules.show', 'settings'), 'active' => request()->routeIs('modules.show') && request()->route('module') === 'settings'],
    ];
@endphp

<nav class="flex flex-col gap-1 px-3 pb-4" aria-label="Principal">
    @foreach ($links as $link)
        <a
            href="{{ $link['href'] }}"
            @class([
                'rounded-lg px-3 py-2 text-sm font-medium focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400',
                'bg-slate-900 text-white' => $link['active'],
                'text-slate-700 hover:bg-slate-100' => ! $link['active'],
            ])
            @if ($link['active']) aria-current="page" @endif
        >
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
